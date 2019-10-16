<?php

namespace MemberBundle\Manager;

use AppBundle\Manager\AbstractManager;
use AppBundle\Manager\SettingManager;
use AppBundle\Widget\Page\ListWidget;
use CommissionBundle\Manager\CommissionManager;
use DateTimeInterface;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\User as MemberUser;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\MemberCommission;
use DbBundle\Entity\MemberRunningCommission;
use DbBundle\Entity\Product;
use DbBundle\Entity\ProductCommission;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\AuditRevision;
use DbBundle\Entity\AuditRevisionLog;
use DbBundle\Repository\CustomerPaymentOptionRepository as MemberPaymentOptionRepository;
use DbBundle\Repository\CustomerProductRepository as MemberProductRepository;
use DbBundle\Repository\CustomerRepository as MemberRepository;
use DbBundle\Repository\MemberCommissionRepository;
use DbBundle\Repository\MemberReferralNameRepository;
use DbBundle\Repository\MemberRunningCommissionRepository;
use DbBundle\Repository\ProductCommissionRepository;
use DbBundle\Repository\ProductRepository;
use DbBundle\Repository\SubTransactionRepository;
use DbBundle\Repository\AuditRevisionRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use MemberBundle\Event\ReferralEvent;
use MemberBundle\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;
use AppBundle\ValueObject\Number;
use TransactionBundle\Manager\TransactionManager;

class MemberManager extends AbstractManager
{
    private $eventDispatcher;
    private $translator;
    private $entityManager;
    private $settingManager;
    private $transactionManager;
    private $precision;

    public function __construct(SettingManager $settingManager)
    {
        $this->settingManager = $settingManager;
    }

    private $commissionManager;
    private $memberProductRepository;
    private $memberReferralNameRepository;

    public function verifyMember(Member $member): void
    {
        $member->verify();
        $this->getRepository()->save($member);
    }

    public function unverifyMember(Member $member): void
    {
        $member->unverify();
        $this->getRepository()->save($member);
    }

    public function getMemberACWallet(Member $member, bool $createIfNotExists = false): ?MemberProduct
    {
        $memberProduct = $this->getMemberProductRepository()->getProductWalletByMember($member->getId());
        if ($createIfNotExists && is_null($memberProduct)) {
            $this->createAcWalletForMember($member);
        }

        return $memberProduct;
    }

    public function createAcWalletForMember(Member $member): MemberProduct
    {
        $product = $this->getProductRepository()->getAcWalletProduct();
        $acWallet = new MemberProduct();
        $acWallet->setUserName($member->getUser()->getUsername());
        $acWallet->setProduct($product);
        $acWallet->setBalance(0);
        $acWallet->setIsActive(true);
        $acWallet->setCustomer($member);
        $member->addProduct($acWallet);

        return $acWallet;
    }

    public function getMemberCommissionForProductForDate(
        Member $member,
        Product $product,
        DateTimeInterface $date
    ): string {
        $memberCommission = $this
            ->getMemberCommissionRepository()
            ->findCommissionOfCustomerForProductBeforeOrOnDate($member, $product, $date);
        if ($memberCommission instanceof MemberCommission) {
            return (string) $memberCommission->getCommission();
        }

        $productCommission = $this
            ->getProductCommissionRepository()
            ->findProductCommissionBeforeOrOnDate($product->getId(), $date);
        if ($productCommission instanceof ProductCommission) {
            return (string) $productCommission->getCommission();
        }

        return '0';
    }

    public function processRunningCommission(array $result, ListWidget $widget, array $data): array
    {
        $memberId = $widget->getPageManager()->getData('customer')->getId();
        $commissionScheduleIds = array_map(function ($record) {
            return $record->getId();
        }, $result['records']);
        $runningCommissions = $this
            ->getMemberRunningCommissionRepository()
            ->getMemberRunningCommissions(
                ['commissionIds' => $commissionScheduleIds, 'memberId' => $memberId],
                count($commissionScheduleIds)
            );
        $runningCommissionsIdKeyed = [];
        foreach ($runningCommissions as $runningCommission) {
            $runningCommissionsIdKeyed[$runningCommission->getCommissionPeriod()->getId()] = $runningCommission;
        }
        $records = [];
        $result['records'] = array_reverse($result['records']);

        $preceedingCommission = null;
        $memberProduct = $this->getMemberProductRepository()->getProductWalletByMember($memberId);
        foreach ($result['records'] as $key => $record) {
            $commission = $runningCommissionsIdKeyed[$record->getId()] ?? new MemberRunningCommission();
            if (!isset($runningCommissionsIdKeyed[$record->getId()])) {
                $commission->setMemberProduct($memberProduct);
            }
            if (is_null($commission->getId()) && $preceedingCommission instanceof MemberRunningCommission) {
                $commission->getRunningCommission($preceedingCommission->getRunningCommission());
            }
            $records[] = [
                'schedule' => $record,
                'commission' => $commission,
            ];
            $preceedingCommission = $commission;
        }
        $records = array_reverse($records);
        if (count($records) > ($data['limit'] - 1)) {
            array_pop($records);
        }

        $result['records'] = $records;
        $result['totalRunningCommission'] = $this
            ->getMemberRunningCommissionRepository()
            ->totalRunningCommissionOfMember($memberId);

        return $result;
    }
    
    public function getMemberLoginHistory(array $filters = []): array
    {
        $startingPointOfArray = 0;
        $numberOfItemsToDisplay = 10;
        if (array_get($filters, 'search.value', false) !== false) {
            $filters['search'] = $filters['search']['value'];
        }
        
        $filters['type'] = MemberUser::USER_TYPE_MEMBER;
        $filters['category'] = AuditRevisionLog::CATEGORY_LOGIN;
        $filters['operation'] = AuditRevisionLog::OPERATION_LOGIN;
        
        $order = [['column' => 'ar.timestamp', 'dir' => 'desc']];
        $returnedData = $this->getAuditRevisionRepository()->getHistoryIPList($filters, $order);
       
        return array_slice($returnedData, $startingPointOfArray, $numberOfItemsToDisplay);
    }

    public function linkMember(Member $member, ?string $referrerCode): array
    {
        $code = Response::HTTP_UNPROCESSABLE_ENTITY;
        $referrerDetails = ['id' => '', 'name' => '', 'username' => ''];
        $notifications = [
            [
                'type' => 'error',
                'title' => $this->getTranslator()->trans(
                    'notification.linkMember.error.title',
                    [],
                    'MemberBundle'
                ),
                'message' => $this->getTranslator()->trans(
                    'notification.linkMember.error.message',
                    ['%code%' => $referrerCode],
                    'MemberBundle'
                ),
            ],
        ];

        if ($referrerCode !== '' && !is_null($referrerCode)) {
            $referrer = $this->getReferrerByReferrerCode($referrerCode);

            if (!is_null($referrer)) {
                $this->getEventDispatcher()->dispatch(Events::EVENT_REFERRAL_LINKED, new ReferralEvent($referrer, $member));
                $member->linkReferrer($referrer);
                $this->getEntityManager()->persist($member);
                $this->getEntityManager()->flush($member);

                $code = Response::HTTP_OK;
                $notifications = [
                    [
                        'type' => 'success',
                        'title' => $this->getTranslator()->trans(
                            'notification.linkMember.success.title',
                            [],
                            'MemberBundle'
                        ),
                        'message' => $this->getTranslator()->trans(
                            'notification.linkMember.success.message',
                            ['%name%' => $member->getFullName(), '%referrer%' => $referrer->getFullName()],
                            'MemberBundle'
                        ),
                    ],
                ];
                $referrerDetails = [
                    'id' => $referrer->getId(),
                    'name' => $referrer->getFullName(),
                    'username' => $referrer->getUsername(),
                ];
            }
        }

        return [
            '__notifications' => $notifications,
            'code' => $code,
            'referrer' => $referrerDetails,
        ];
    }

    public function getReferrerByReferrerCode(?string $referrerCode): ?Member
    {
        $referrer = null;

        if (!is_null($referrerCode) && $referrerCode !== '') {
            $memberReferralName = $this->getMemberReferralNameRepository()->findOneByName($referrerCode);

            if (!is_null($memberReferralName)) {
                $referrer = $memberReferralName->getMember();
            }
        }

        return $referrer;
    }

    public function setMemberReferralNameRepository(MemberReferralNameRepository $memberReferralNameRepository): void
    {
        $this->memberReferralNameRepository = $memberReferralNameRepository;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function getEntityManager($name = 'default'): EntityManager
    {
        return $this->entityManager;
    }

    public function getOriginSetting(): array
    {
        return $this->settingManager->getSetting('origin.origins') ?? [];
    }

    public function getCurrentPeriodReferralTurnoversAndCommissions(int $referrerId, DateTimeInterface $currentDate, array $filters): array
    {   
        if (empty($filters['dwlDateFrom'] ?? null) || empty($filters['dwlDateTo'] ?? null)) {
            $currentPeriod = $this->getCommissionManager()->getCommissionPeriodForDate($currentDate);

            if (!is_null($currentPeriod)) {
                $filters['dwlDateFrom'] = $currentPeriod->getDWLDateFrom()->format('Y-m-d');
                $filters['dwlDateTo'] = $currentPeriod->getDWLDateTo()->format('Y-m-d');
            }
        }

        $piwiWallet = $this->getProductRepository()->getPiwiWalletProduct();
        $filters['piwiWalletProductId'] = $piwiWallet->getId();
        $turnoversWinLossCommissions = $this->getTurnoversAndCommissionsByMember($referrerId, $filters);

        return $turnoversWinLossCommissions;
    }

    public function getTurnoversAndCommissionsByMember(int $referrerId, array $filters): array
    {
        $memberRepository = $this->getRepository();
        $subTransactionRepository = $this->getSubTransactionRepository();

        $filters['hideZeroTurnover'] = array_get($filters, 'hideZeroTurnover', false);
        $orders = [
            ['column' => $filters['orderBy'], 'dir' => 'ASC'],
            ['column' => $filters['orderBy'] == 'productName' ? 'memberId' : 'productName', 'dir' => 'ASC'],
        ];

        $result = [
            'recordsTotal' => $memberRepository->getReferralProductListTotalCountByReferrer($filters, $referrerId),
            'limit' => $filters['limit'],
            'page' => $filters['page'],
            'filters' => $filters,
        ];

        if (!array_get($filters, 'hideZeroTurnover')) {
            $result['records'] = $memberRepository->getReferralProductListByReferrer(
                $filters, $orders, $referrerId, $filters['offset'], $filters['limit']
            );
            $result['recordsFiltered'] = $memberRepository->getReferralProductListFilterCountByReferrer($filters, $referrerId);
            $filters['memberProductIds'] = array_column($result['records'], 'memberProductId');
        }

        $filters['startDate'] = $this->getSettingManager()->getSetting('commission.startDate');
        $this->precision = isset($filters['precision']) ? $filters['precision'] : 2;



        $memberReferralsTurnoversAndCommissions = $subTransactionRepository
            ->getReferralTurnoverWinLossCommissionByReferrer(
                $filters, $orders, $referrerId, $filters['offset'], $filters['limit']
            );

        if (!array_get($filters, 'hideZeroTurnover')) {
            $memberReferralsTurnoversAndCommissions = array_column($memberReferralsTurnoversAndCommissions,null,'memberProductId');

            foreach ($result['records'] as &$row) {
                $row = array_merge($row, [
                    'totalTurnover' => $this->formatCommissionAmount(0),
                    'totalWinLoss' => $this->formatCommissionAmount(0),
                    #'totalAffiliateCommission' => $this->formatCommissionAmount(0),
                    'totalAffiliateRevenueShare' => $this->formatCommissionAmount(0),
                    'totalAffiliateBonus' => $this->formatCommissionAmount(0),
                    'totalRevenueShare' => $this->formatCommissionAmount(0),
                    'totalBonus' => $this->formatCommissionAmount(0),
                ]);

                if (array_has($memberReferralsTurnoversAndCommissions, $row['memberProductId'])) {
                    $row = array_get($memberReferralsTurnoversAndCommissions, $row['memberProductId']);

                    $row['totalTurnover'] = $this->formatCommissionAmount($row['totalTurnover']);
                    $row['totalWinLoss'] = $this->formatCommissionAmount($row['totalWinLoss']);
                    #$row['totalAffiliateCommission'] = $this->formatCommissionAmount($row['totalAffiliateCommission']);
                    $row['totalAffiliateRevenueShare'] = $this->formatCommissionAmount($row['totalAffiliateCommission']);
                    $row['totalAffiliateBonus'] = $this->formatCommissionAmount($row['totalAffiliateCommission']);
                    $row['totalRevenueShare'] = $this->formatCommissionAmount($row['totalAffiliateCommission']);
                    $row['totalBonus'] = $this->formatCommissionAmount($row['totalAffiliateCommission']);
                }
            }
        } else {
            $result['records'] = $memberReferralsTurnoversAndCommissions;
            $result['recordsFiltered'] = $subTransactionRepository->getReferralTurnoverWinLossCommissionFilterCountByReferrer($filters, $referrerId);

            foreach ($result['records'] as &$row) {
                    $row['totalTurnover'] = $this->formatCommissionAmount($row['totalTurnover']);
                    $row['totalWinLoss'] = $this->formatCommissionAmount($row['totalWinLoss']);
                    #$row['totalAffiliateCommission'] = $this->formatCommissionAmount($row['totalAffiliateCommission']);
                    $row['totalAffiliateRevenueShare'] = $this->formatCommissionAmount($row['totalAffiliateCommission']);
                    $row['totalAffiliateBonus'] = $this->formatCommissionAmount($row['totalAffiliateCommission']);
                    $row['totalRevenueShare'] = $this->formatCommissionAmount($row['totalAffiliateCommission']);
                    $row['totalBonus'] = $this->formatCommissionAmount($row['totalAffiliateCommission']);
            }
        }

        $currencies = $this->getMemberProductRepository()->getReferralCurrenciesByReferrer($referrerId);
        $totals = array_column(
            $subTransactionRepository
            ->getTotalReferralTurnoverWinLossCommissionByReferrer($filters, $referrerId),
            null,
            'currencyCode'
        );

        $totalAffiliateCommission = new Number(0);
        foreach ($totals as $total) {
            $totalAffiliateCommission = $totalAffiliateCommission->plus($total['totalAffiliateCommission']);
        }

        foreach ($currencies as &$currency) {
            $currency['totalTurnover'] = $this->formatCommissionAmount(0);
            $currency['totalWinLoss'] = $this->formatCommissionAmount(0);

            if (array_has($totals, $currency['currencyCode'])) {
                $currency = array_get($totals, $currency['currencyCode']);

                $currency['totalTurnover'] = $this->formatCommissionAmount($currency['totalTurnover']);
                $currency['totalWinLoss'] = $this->formatCommissionAmount($currency['totalWinLoss']);
            }

            #$currency['totalAffiliateCommission'] = $this->formatCommissionAmount($totalAffiliateCommission->toString());
            $currency['totalAffiliateRevenueShare'] = $this->formatCommissionAmount($totalAffiliateCommission->toString());
            $currency['totalAffiliateBonus'] = $this->formatCommissionAmount($totalAffiliateCommission->toString());
        }

        $result['totals'] = $currencies;
        $result['period'] = [
            'dwlDateFrom' => $filters['dwlDateFrom'],
            'dwlDateTo' => $filters['dwlDateTo'],
        ];

        return $result;
    }

    public function generateTurnoverCommissionReport(int $referrerId, array $filters): void
    {
        $separator = ',';
        $member = $this->getRepository()->find($referrerId);
        $memberCurrencyCode = $member->getCurrencyCode();
        $filters['limit'] = $this->getRepository()->getReferralProductListTotalCountByReferrer($filters, $referrerId);
        $filters['offset'] = 0;
        $result = $this->getTurnoversAndCommissionsByMember($referrerId, $filters);

        $csvReport = '';
        $csvReport .= 'Member: ' . $member->getFullName() . ' (' . $member->getUsername() . ')' . "\n\n";
        echo $csvReport;

        $csvReport = '';
        $csvReport .= $filters['orderBy'] == 'productName' ? 'Product, Member ID' : 'Member ID, Product';
        $csvReport .= ', Total Turnover,  Total W/L, Affiliate Commission';
        $csvReport .= "\n";
        echo $csvReport;

        foreach ($result['records'] as $record) {
            $csvReport = '';

            if ($filters['orderBy'] == 'productName') {
                $csvReport .= $record['productName'] . $separator;
                $csvReport .= $record['memberId'] . $separator;
            } else {
                $csvReport .= $record['memberId'] . $separator;

                if (!is_null($record['memberProductId'])) {
                    $csvReport .= $record['productName'] . $separator;
                }
            }

            if (!is_null($record['memberProductId'])) {
                $csvReport .= '"' . $record['currencyCode'] . ' ' . $record['totalTurnover']['rounded'] . '"' . $separator;
                $csvReport .= '"' . $record['currencyCode'] . ' ' . $record['totalWinLoss']['rounded']  . '"' . $separator;
                $csvReport .= '"' . $memberCurrencyCode . ' ' . $record['totalAffiliateCommission']['rounded']  . '"' . $separator;
            } else {
                $csvReport .= 'No products' . $separator . $separator . $separator . $separator;
            }

            $csvReport .= "\n";

            echo $csvReport;
        }

        $totalTurnover = [];
        $totalWinLoss = [];
        $totalAffiliateCommission = [];

        foreach ($result['totals'] as $total) {
            $currencyCode = $total['currencyCode'];
            $totalTurnover[$currencyCode] = $total['totalTurnover'];
            $totalWinLoss[$currencyCode] = $total['totalWinLoss'];
            $totalAffiliateCommission[$currencyCode] = $total['totalAffiliateCommission'];
        }

        $csvReport = '';
        $csvReport .= '"' . sprintf('Date Covered: %s - %s', $result['period']['dwlDateFrom'], $result['period']['dwlDateTo']) . "\n" . '",,';
        $csvReport .= '"';
        foreach ($totalTurnover as $currencyCode => $total) {
            $csvReport .= $currencyCode . ' ' . $total['rounded'] . "\n";

        }
        $csvReport .= '",';

        $csvReport .= '"';
        foreach ($totalWinLoss as $currencyCode => $total) {
            $csvReport .= $currencyCode . ' ' . $total['rounded'] . "\n";
        }
        $csvReport .= '",';

        $csvReport .= '"';
        $csvReport .= $memberCurrencyCode . ' ' . $totalAffiliateCommission[$memberCurrencyCode]['rounded'] . "\n";
        $csvReport .= '"';

        echo $csvReport;
    }

    public function isPaymentOptionIdBitcoin(int $paymentOptionId): bool
    {
        $memberPaymentOption = $this->getMemberPaymentOptionRepository()->find($paymentOptionId);
        $isPaymentBitcoin = $memberPaymentOption->getPaymentOption()->isPaymentBitcoin();
        
        return $isPaymentBitcoin;
    }

    public function getTransactionStatusFilterList(): array
    {
        $statusList = $this->getTransactionManager()->getTransactionStatus();
        $status = [];

        foreach ($statusList as $key => $value) {
            $status[] = [
                'label' => $value['label'],
                'value' => $key,
            ];
        }

        return $status;
    }

    public function updateMemberPaymentOptionBitcoinAddress(array $transaction): void
    {
        $memberPaymentOptionId = $transaction['paymentOption'];
        $bitcoinAddress = $transaction['details']['bitcoin']['receiver_unique_address'];
        $memberPaymentOption = $this->getMemberPaymentOptionRepository()->find($memberPaymentOptionId);
        if ($memberPaymentOption->getBitcoinField() != $bitcoinAddress) {
            $memberPaymentOption->setBitcoinAddress($bitcoinAddress);

            $this->getEntityManager()->persist($memberPaymentOption);
            $this->getEntityManager()->flush($memberPaymentOption);
        }
    }

    public function setTransactionManager(TransactionManager $transactionManager): void
    {
        $this->transactionManager = $transactionManager;
    }

    public function setSettingManager(SettingManager $settingManager): void
    {
        $this->settingManager = $settingManager;
    }

    public function setCommissionManager(CommissionManager $commissionManager): void
    {
        $this->commissionManager = $commissionManager;
    }

    public function setMemberProductRepository(MemberProductRepository $memberProductRepository): void
    {
        $this->memberProductRepository = $memberProductRepository;
    }

    public function setMemberPaymentOptionRepository(MemberPaymentOptionRepository $memberPaymentOptionRepository): void
    {
        $this->memberPaymentOptionRepository = $memberPaymentOptionRepository;
    }

    public function getMemberLocale(Member $member): string
    {
        $defaultLocale = $this->settingManager->getSetting('member.locale.default');
        $memberLocale = $member->getDetail('locale');
    }

    protected function getRepository(): MemberRepository
    {
        return $this->getDoctrine()->getRepository(Member::class);
    }
    
    protected function getAuditRevisionRepository(): AuditRevisionRepository
    {
        return $this->getDoctrine()->getRepository(AuditRevision::class);
    }

    private function formatCommissionAmount(string $amount): array
    {
        return [
            'original' => $amount,
            'rounded' => Number::format($amount, ['precision' => $this->precision]),
        ];
    }

    protected function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->getDoctrine()->getRepository(Product::class);
    }

    private function getMemberCommissionRepository(): MemberCommissionRepository
    {
        return $this->getDoctrine()->getRepository(MemberCommission::class);
    }

    private function getProductCommissionRepository(): ProductCommissionRepository
    {
        return $this->getDoctrine()->getRepository(ProductCommission::class);
    }

    private function getMemberRunningCommissionRepository(): MemberRunningCommissionRepository
    {
        return $this->getDoctrine()->getRepository(MemberRunningCommission::class);
    }

    private function getMemberReferralNameRepository(): MemberReferralNameRepository
    {
        return $this->memberReferralNameRepository;
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    private function getSubTransactionRepository(): SubTransactionRepository
    {
        return $this->getDoctrine()->getRepository(SubTransaction::class);
    }

    private function getSettingManager(): SettingManager
    {
        return $this->settingManager;
    }

    private function getCommissionManager(): CommissionManager
    {
        return $this->commissionManager;
    }

    private function getTransactionManager(): TransactionManager
    {
        return $this->transactionManager;
    }

    private function getMemberProductRepository(): MemberProductRepository
    {
        return $this->memberProductRepository;
    }

    private function getMemberPaymentOptionRepository(): MemberPaymentOptionRepository
    {
        return $this->memberPaymentOptionRepository;
    }
}
