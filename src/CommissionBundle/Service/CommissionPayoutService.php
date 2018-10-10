<?php

namespace CommissionBundle\Service;

use AppBundle\Manager\SettingManager;
use CommissionBundle\Manager\CommissionManager;
use DateTime;
use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\DWL;
use DbBundle\Entity\MemberRunningCommission;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\CommissionPeriodRepository;
use DbBundle\Repository\CustomerProductRepository as MemberProductRepository;
use DbBundle\Repository\CustomerRepository as MemberRepository;
use DbBundle\Repository\MemberRunningCommissionRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use TransactionBundle\Manager\TransactionManager;

class CommissionPayoutService implements LoggerAwareInterface
{
    use ContainerAwareTrait;
    
    const RESUBMIT_DEFAULT_ACTION = 0;
    
    private $logger;
    
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
    
    public function computeCommissionForMember(CommissionPeriod $period, Member $member): void
    {
        try {
            $now = new DateTime('now');
            $memberRunningCommission = $this->generateMemberRunningCommission($period, $member);
            $memberRunningCommission->forRecomputation();
            $this->isConditionMet($memberRunningCommission, $period);

            $offset = 0;
            $limit = 20;
            do {
                $this->reconnectToDatabase();
                $entities = $this->getCommissionPeriodRepository()->getTransactionsForCommissionPeriod($period, $member, $limit, $offset);
                $loop = false;
                $offset += $limit;
                $transactions = [];
                $dwls = [];
                foreach ($entities as $entity) {
                    if ($entity instanceof Transaction) {
                        $transactions[$entity->getId()] = $entity;
                    } elseif ($entity instanceof DWL) {
                        $dwls[$entity->getId()] = $entity;
                    }
                }
                
                foreach ($transactions as $transaction) {
                    $loop = true;
                    $this->processMemberRunningCommission($transaction, $dwls[$transaction->getDwlId()], $member, $period, $memberRunningCommission);
                }
                $transactions = [];
                $dwls = [];
            } while ($loop);
            $this->reconnectToDatabase();
            $memberRunningCommission->setProcessStatusToComputed();
            $this->makePayout($memberRunningCommission);
            $this->getMemberRunningCommissionRepository()->save($memberRunningCommission);
            $this->updateSucceedingMemberRunningCommisison($memberRunningCommission);
        } catch (\Exception $e) {
            if ($memberRunningCommission->isComputing()) {
                $memberRunningCommission->setProcessStatusToComputationError();
            } elseif ($memberRunningCommission->isPaying()) {
                $memberRunningCommission->setProcessStatusToPayError();
            }
            
            $memberRunningCommission->setError($e->getMessage());
            
            throw $e;
        }
    }
    
    public function payoutCommissionForMember(CommissionPeriod $period, Member $member): void
    {
        $memberRunningCommission = $this->generateMemberRunningCommission($period, $member);
        $this->makePayout($memberRunningCommission);
    }
    
    public function makePayout(MemberRunningCommission $memberRunningCommission): void
    {
        try {
            $now = new DateTime('now');
            $period = $memberRunningCommission->getCommissionPeriod();
            if (!($period->getPayoutAt() <= $now && ($period->isSuccessfullPayout() || $period->isExecutingPayout()) && $memberRunningCommission->isConditionMet())) {
                return;
            }
            $memberRunningCommission->setProcessStatusToPaying();
            if ($memberRunningCommission->hasCommissionTransaction()) {
                $transaction = $memberRunningCommission->getCommissionTransaction();
                $subTransaction = $transaction->getFirstSubTransaction();
                $subTransaction->revertCustomerBalance();
                $subTransaction->setAmount($memberRunningCommission->getTotalCommission());
                $transaction->setDate(new DateTime());
                $transaction->setCommissionConvertions($memberRunningCommission->getTotalCommissionConvertion());
            } else {
                $memberProduct = $memberRunningCommission->getMemberProduct();
                $member = $memberProduct->getCustomer();
                $currency = $memberRunningCommission->getCurrency();

                if (
                    is_null($memberRunningCommission->getId())
                    && $memberRunningCommission->getTotalCommission() === '0'
                ) {
                    return;
                }

                $transaction = new Transaction();
                $transaction->setCustomer($member);
                $transaction->setType(Transaction::TRANSACTION_TYPE_COMMISSION);
                $transaction->setCurrency($member->getCurrency());
                $transaction->setNumber(sprintf(
                    '%s-%s-%s-%s',
                    date('Ymd-His'),
                    Transaction::TRANSACTION_TYPE_COMMISSION,
                    $memberRunningCommission->getCommissionPeriod()->getId(),
                    $memberProduct->getId()
                ));
                $transaction->setDate(new DateTime());

                $subTransaction = new SubTransaction();
                $subTransaction->setType(Transaction::TRANSACTION_TYPE_DEPOSIT);
                $subTransaction->setCustomerProduct($memberRunningCommission->getMemberProduct());
                $subTransaction->setAmount($memberRunningCommission->getTotalCommission());

                $transaction->addSubTransaction($subTransaction);
                $transaction->setCommissionConvertions($memberRunningCommission->getTotalCommissionConvertion());
            }

            $action  = 'new';
            if (!$transaction->isNew()) {
                $action = 0;
            }
            $this->getTransactionManager()->processTransaction($transaction, $action);
            $memberRunningCommission->setCommissionTransaction($transaction);
            $memberRunningCommission->setProcessStatusToPaid();
            $this->getCommissionPeriodRepository()->save($memberRunningCommission, true);
        } catch (\Exception $e) {
            if ($memberRunningCommission->isComputing()) {
                $memberRunningCommission->setProcessStatusToComputationError();
            } elseif ($memberRunningCommission->isPaying()) {
                $memberRunningCommission->setProcessStatusToPayError();
            }
            
            $memberRunningCommission->setError($e->getMessage());
            
            throw $e;
        }
    }
    
    private function updateSucceedingMemberRunningCommisison(MemberRunningCommission $memberRunningCommission): void
    {
        $this->reconnectToDatabase();
        $succeedingMemberRunningCommission = $memberRunningCommission->getSucceedingRunningCommission();
        if ($succeedingMemberRunningCommission instanceof MemberRunningCommission) {
            $succeedingMemberRunningCommission->setPreceedingRunningCommission($memberRunningCommission);
            $this->getMemberRunningCommissionRepository()->save($succeedingMemberRunningCommission);
            $this->updateSucceedingMemberRunningCommisison($succeedingMemberRunningCommission);
        }
    }
    
    public function isConditionMet(
        MemberRunningCommission $memberRunningCommission,
        CommissionPeriod $commissionPeriod
    ): bool {
        $conditions = $commissionPeriod->getConditions();
        $conditionMet = false;
        $member = $memberRunningCommission->getMemberProduct()->getCustomer();
        $values = [];
        $expression = [];
        foreach ($conditions as $condition) {
            $expression[] = $condition['field'] . ' ' . $condition['value'];
            if ($condition['field'] === 'active_member_product') {
                $values['active_member_product'] = $this
                    ->getMemberProductRepository()
                    ->getTotalActiveMemberProductByReferrer($member)['totalCustomerProducts'];
            } elseif ($condition['field'] === 'active_member') {
                $values['active_member'] = $this->getMemberRepository()->getCustomerListFilterCount([
                    'affiliate' => $member->getId(),
                    'status' => Member::CUSTOMER_ENABLED,
                ]);
            }
        }

        $expLang = new ExpressionLanguage();
        $conditionMet = $expLang->evaluate(implode(' AND ', $expression), $values);

        if ($conditionMet) {
            $memberRunningCommission->setStatusToMet();
        } else {
            $memberRunningCommission->setStatusToUnMet();
        }

        return $conditionMet;
    }
    
    private function generateMemberRunningCommission(CommissionPeriod $period, Member $member): MemberRunningCommission
    {
        $acWallet = $this->getMemberProductRepository()->getProductWalletByMember($member->getId());
        
        $memberRunningCommission = $this
            ->getMemberRunningCommissionRepository()
            ->getMemberRunningCommissionFromCommissionPeriod($period->getId(), $member->getId());
        if (!($memberRunningCommission instanceof MemberRunningCommission)) {
            $memberRunningCommission = new MemberRunningCommission();
            $memberRunningCommission->setMemberProduct($acWallet);
            $memberRunningCommission->setCommissionPeriod($period);
            $preceedingMemberRunningCommission = $this->getMemberRunningCommissionRepository()->getPreceedingMemberRunningCommission($memberRunningCommission);
            if ($preceedingMemberRunningCommission instanceof MemberRunningCommission) {
                $memberRunningCommission->setPreceedingRunningCommission($preceedingMemberRunningCommission);
            }
        }
        
        $this->getMemberRunningCommissionRepository()->save($memberRunningCommission);
        
        return $memberRunningCommission;
        
    }
    
    private function processMemberRunningCommission(
        Transaction $transaction,
        DWL $dwl,
        Member $referrer,
        CommissionPeriod $period,
        MemberRunningCommission $memberRunningCommission
    ): void {
        try {
            $this->getEntityManager()->beginTransaction();
            if (empty($transaction->getComputedAmount())) {
                $this->getCommissionManager()->setCommissionInformationForTransaction($transaction, $dwl);
            }
            $commission = $transaction->getCommissionForCurrency($transaction->getCurrency()->getCode());
            $convertedCommission = $transaction->getCommissionForCurrency($memberRunningCommission->getCurrency()->getCode());
            $memberRunningCommission->addCommission($convertedCommission);
            $memberRunningCommission->addCommissionConvertion(
                $transaction->getCurrency()->getCode(),
                $commission,
                $convertedCommission
            );
            $transaction->setMemberRunningCommissionId($memberRunningCommission->getId());
            $this->getCommissionPeriodRepository()->save($transaction);
            $this->getEntityManager()->commit();
        } catch (Exception $ex) {
            $this->getEntityManager()->rollback();
            throw $ex;
        }
    }
    
    public function reconnectToDatabase(): void
    {
        $this->getEntityManager()->getConnection()->reconnect();
    }
    
    private function getCommissionManager(): CommissionManager
    {
        return $this->container->get('commission.manager');
    }
    
    private function getTransactionManager(): TransactionManager
    {
        return $this->container->get('transaction.manager');
    }
    
    private function getMemberRunningCommissionRepository(): MemberRunningCommissionRepository
    {
        return $this->getDoctrine()->getRepository(MemberRunningCommission::class);
    }
    
    private function getMemberRepository(): MemberRepository
    {
        return $this->getDoctrine()->getRepository(Member::class);
    }
    
    private function getMemberProductRepository(): MemberProductRepository
    {
        return $this->getDoctrine()->getRepository(MemberProduct::class);
    }
    
    private function getCommissionPeriodRepository(): CommissionPeriodRepository
    {
        return $this->getDoctrine()->getRepository(CommissionPeriod::class);
    }
    
    private function getEntityManager(): EntityManager
    {
        return $this->getDoctrine()->getManager();
    }

    private function getDoctrine(): Registry
    {
        return $this->container->get('doctrine');
    }
    
    private function getSettingManager(): SettingManager
    {
        return $this->container->get('app.setting_manager');
    }
}
