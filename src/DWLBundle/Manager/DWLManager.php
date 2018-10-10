<?php

namespace DWLBundle\Manager;

use AppBundle\Manager\AbstractManager;
use AppBundle\ValueObject\Number;
use CommissionBundle\Manager\CommissionManager;
use DateTime;
use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\DWL;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\CommissionPeriodRepository;
use DbBundle\Repository\CustomerProductRepository;
use DbBundle\Repository\DWLRepository;
use DbBundle\Repository\SubTransactionRepository;
use DbBundle\Repository\TransactionRepository;
use Doctrine\ORM\NoResultException;
use DWLBundle\Entity\DWLItem;
use DWLBundle\Repository\TransactionRepository as TransactionRepository2;
use JMS\JobQueueBundle\Entity\Job;
use MediaBundle\Manager\MediaManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;

/**
 * Description of DWLManager.
 *
 * @author cnonog
 */
class DWLManager extends AbstractManager
{
    /**
     * Use for submitting of DWL
     * Restrict submitting of dwl if there are errors
     */
    const SUBMIT_TYPE_RESTRICT = 'restrict';

    /**
     * Use for submitting of DWL
     * Force submitting of dwl even there are error
     */
    const SUBMIT_TYPE_FORCE = 'force';

    public function submit($dwl, $submitType = self::SUBMIT_TYPE_RESTRICT): array
    {
        $filesystem = new Filesystem();

        if ($dwl->getStatus() == DWL::DWL_STATUS_PROCESSED) {
            $file = $this->getMediaManager()->getFile(sprintf('dwl/%s_v_%s.json', $dwl->getId(), $dwl->getVersion()));
            $hn = $file->openFile('r');
            $data = json_decode($hn->fread($hn->getSize()), true);
            $hn = null;

            $hasErrors = false;
            if ($submitType === self::SUBMIT_TYPE_RESTRICT) {
                foreach ($data['items'] as $item) {
                    if (!empty($item['errors'])) {
                        $hasErrors = true;
                        break;
                    }
                }
            }

            if ($hasErrors) {
                return ['success' => false, 'error' => 'notifications.submit.error.message'];
            }

            $dwl->setStatus(DWL::DWL_STATUS_SUBMITED);
            $this->save($dwl);

            $progressFile = sprintf(
                '%sprogress_%s_v_%s.json',
                $this->getMediaManager()->getPath('dwl'),
                $dwl->getId(),
                $dwl->getVersion()
            );
            if (!$filesystem->exists($progressFile)) {
                $filesystem->touch($progressFile);
            }

            $logDir = $this->getContainer()->get('kernel')->getLogDir();
            $rootDir = $this->container->get('kernel')->getRootDir();
            $command = [
                $this->container->getParameter('php_command'),
                "$rootDir/console",
                'dwl:submit',
                $dwl->getId(),
                $this->getUser()->getUsername(),
                '--env=' . $this->getContainer()->get('kernel')->getEnvironment(),
                '-vvv',
            ];

            if ($submitType === self::SUBMIT_TYPE_FORCE) {
                $command[] = '--submit=force';
            }
            # $command[] = '&';

            $logName = sprintf(
                '%s_%s',
                $this->getContainer()->get('kernel')->getEnvironment(),
                $this->getContainer()->getParameter('upload_dwl_filename')
            );
            
            $commandArguments = [
                $dwl->getId(),
                $this->getUser()->getUsername(),
                '--env',
                $this->getContainer()->get('kernel')->getEnvironment(),
            ];
            
            if ($submitType === self::SUBMIT_TYPE_FORCE) {
                $commandArguments[] = '--submit=force';
            }
            
            $job = new Job('dwl:submit', $commandArguments, true, 'dwl-submit');
            $this->getEntityManager()->persist($job);
            $this->getEntityManager()->flush($job);
            
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'notifications.submit.error.message'];
        }
    }

    public function upload($file, $data)
    {
        $filesystem = new Filesystem();
        $status = $this->getMediaManager()->uploadFile($file, 'dwl');
        $fileInfo = $this->getMediaManager()->getFile($status['folder'] . '/' . $status['filename'], true);

        $status = $this->getMediaManager()->renameFile(
            $fileInfo['folder'] . '/' . $fileInfo['filename'],
            $fileInfo['folder'] . '/' . $data->getId() . '_v_' . $data->getVersion()
        );

        $logDir = $this->getContainer()->get('kernel')->getLogDir();
        $rootDir = $this->container->get('kernel')->getRootDir();

        $progressFile = sprintf(
            '%sprogress_%s_v_%s.json',
            $this->getMediaManager()->getPath('dwl'),
            $data->getId(),
            $data->getVersion()
        );

        if (!$filesystem->exists($progressFile)) {
            $filesystem->touch($progressFile);
            $updatedAt = base64_encode($data->getUpdatedAt()->format('Y-m-d H:i:s'));
            file_put_contents($progressFile, json_encode(['_v' => $updatedAt, 'status' => $data->getStatus(), 'process' => 0, 'total' => 0]));
        }

        $command = [
            $this->container->getParameter('php_command'),
            "$rootDir/console",
            'dwl:file:process',
            $data->getId(),
            $this->getUser()->getUsername(),
            '--env=' . $this->getContainer()->get('kernel')->getEnvironment(),
            '-vvv',
        ];
        $logName = sprintf(
            '%s_%s',
            $this->getContainer()->get('kernel')->getEnvironment(),
            $this->getContainer()->getParameter('upload_dwl_filename')
        );
        #$process = new Process(implode(' ', $command) . " >> $logDir/$logName 2>&1 &");
        #$process->start();
        
        $job = new Job('dwl:file:process', [
            $data->getId(),
            $this->getUser()->getUsername(),
            '--env',
            $this->getContainer()->get('kernel')->getEnvironment(),
        ], true, 'dwl-upload');
        $this->getEntityManager()->persist($job);
        $this->getEntityManager()->flush($job);

        return $status['file'];
    }

    public function getList(Request $request)
    {
        $filters = $request->get('filters', []);
        $orders = $request->get('orders', []);
        $limit = (int) $request->get('limit', 20);
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $limit;

        $dwl = $this->getRepository()->findDWL($filters, $orders, $limit, $offset);
        $recordsFiltered = $this->getRepository()->getTotal($filters);
        $recordsTotal = $this->getRepository()->getTotal();

        $result = [
            'records' => $dwl,
            'recordsFiltered' => $recordsFiltered,
            'recordsTotal' => $recordsTotal,
            'limit' => $limit,
            'page' => $page,
        ];

        return $result;
    }

    /**
     *
     * @param \DWLBundle\Entity\DWL $dwl
     * @param iny                   $version
     * @param string                $username
     * @param array|null            $data
     *
     * @return DWLItem
     *
     * @throws NoResultException
     */
    public function getItem($dwl, $version, $username, &$data = null)
    {
        $filesystem = new Filesystem();
        $fileName = sprintf('%s_v_%s.json', $dwl->getId(), $version);
        $file = $this->getMediaManager()->getFile('dwl/' . $fileName);
        $hn = $file->openFile('r');
        $data = json_decode($hn->fread($hn->getSize()), true);
        $hn = null;
        $hasItem = false;
        foreach ($data['items'] as $item) {
            if ($username === $item['username']) {
                $hasItem = true;
                break;
            }
        }

        if ($hasItem) {
            $dwlItem = new DWLItem();

            $customerProduct = $this
                ->getCustomerProductRepository()
                ->findByUsernameProductAndCurrency($username, $dwl->getProduct()->getId(), $dwl->getCurrency()->getId());

            if ($customerProduct === null) {
                throw new NoResultException();
            }
            if ($customerProduct->getCustomer()->getCurrency() !== $dwl->getCurrency()) {
                throw new NoResultException();
            }
            if ($customerProduct->getProduct()->getId() !== $dwl->getProduct()->getId()) {
                throw new NoResultException();
            }

            if ($dwl->getVersion() == $version && $dwl->getStatus() == DWL::DWL_STATUS_COMPLETED) {
                $subTransaction = $this->getDWLTransactionRepository()->getSubtransactionByProductAndDwlId($customerProduct->getId(), $dwl->getId());

                if ($subTransaction !== null) {
                    $transaction = $subTransaction->getParent();
                    $transaction->getSubTransactions();
                    $dwlItem->setTransaction($transaction);
                    $dwlItem->setSubTransaction($subTransaction);
                } else {
                    throw new NoResultException();
                }
            }

            $dwlItem->setDwl($dwl);
            $dwlItem->setCustomerProduct($customerProduct);
            $dwlItem->setTurnover($item['turnover']);
            $dwlItem->setGross($item['gross']);
            $dwlItem->setWinLoss($item['winLoss']);
            $dwlItem->setCommission($item['commission']);
            $dwlItem->setAmount($item['amount']);
            $dwlItem->setUsername($username);
            $dwlItem->setUpdatedAt($dwl->getUpdatedAt());
            $dwlItem->setVersion($version);

            return $dwlItem;
        } else {
            throw new NoResultException();
        }
    }

    /**
     * @param DWL      $dwl
     * @param DWLItem $dwlItem
     * @param array                     $data
     *
     * @return \DbBundle\Entity\DWLItem
     *
     * @throws NoResultException
     * @throws \Exception
     */
    public function saveItem($dwl, $dwlItem, $data)
    {
        try {
            $this->getRepository()->beginTransaction();
            $hasItem = false;
            foreach ($data['items'] as &$item) {
                if ($item['username'] === $dwlItem->getUsername()) {
                    $hasItem = true;
                    break;
                }
            }
            if (!$hasItem) {
                throw new NoResultException();
            }

            $total = $data['total'];
            $total['turnover'] = (new Number($total['turnover']))->minus($item['turnover'])->toFloat();
            $total['grossCommission'] = (new Number($total['grossCommission']))->minus($item['gross'])->toFloat();
            $total['memberWinLoss'] = (new Number($total['memberWinLoss']))->minus($item['winLoss'])->toFloat();
            $total['memberCommission'] = (new Number($total['memberCommission']))->minus($item['commission'])->toFloat();
            $total['memberAmount'] = (new Number($total['memberAmount']))->minus($item['amount'])->toFloat();
            $oldAmount = $item['amount'];
            $item['turnover'] = $dwlItem->getTurnover();
            $item['gross'] = $dwlItem->getGross();
            $item['winLoss'] = $dwlItem->getWinLoss();
            $item['commission'] = $dwlItem->getCommission();
            $item['amount'] = $dwlItem->getAmount();
            $item['calculatedAmount'] = $dwlItem->getCalculatedAmount();
            $item['errors'] = [];

            $total['turnover'] = (new Number($total['turnover']))->plus($item['turnover'])->toFloat();
            $total['grossCommission'] = (new Number($total['grossCommission']))->plus($item['gross'])->toFloat();
            $total['memberWinLoss'] = (new Number($total['memberWinLoss']))->plus($item['winLoss'])->toFloat();
            $total['memberCommission'] = (new Number($total['memberCommission']))->plus($item['commission'])->toFloat();
            $total['memberAmount'] = (new Number($total['memberAmount']))->plus($item['amount'])->toFloat();

            $dwl->setDetail('total', $total);

            $this->getRepository()->save($dwl);
            if ($dwl->getStatus() == DWL::DWL_STATUS_COMPLETED) {
                $subTransaction = $dwlItem->getSubTransaction();
                $subTransaction->getCustomerProduct();
                $transaction = $dwlItem->getTransaction();
                $transaction->getSubTransactions();

                $customerProductBalance = new Number($subTransaction->getCustomerProduct()->getBalance());
                $customerProductBalance = $customerProductBalance->minus($subTransaction->getDetail(
                    'convertedAmount',
                    $subTransaction->getAmount()
                ));
                $subTransaction->getCustomerProduct()->setBalance($customerProductBalance . '');
                $transaction->setDate(new DateTime());
                $transaction->setNumber(sprintf(
                    '%s-%s-%s-%s',
                    date('Ymd-His'),
                    Transaction::TRANSACTION_TYPE_DWL,
                    $dwl->getId(),
                    $item['id']
                ));

                $subTransaction->setAmount($item['amount']);

                $subTransaction->setDetail('dwl.id', $dwl->getId());
                $subTransaction->setDetail('dwl.turnover', $item['turnover']);
                $subTransaction->setDetail('dwl.gross', $item['gross']);
                $subTransaction->setDetail('dwl.winLoss', $item['winLoss']);
                $subTransaction->setDetail('dwl.commission', $item['commission']);

                if ($subTransaction->getDetail('dwl.customer.balance', null) === null) {
                    $subTransaction->setDetail('dwl.customer.balance', $customerProductBalance);
                }

                $this->getTransactionManager()->processTransactionSummary($transaction);
                $this->getCommissionManager()->setCommissionInformationForTransaction($transaction, $dwl);
                $this->getTransactionManager()->endTransaction($transaction);
                $this->getEntityManager()->persist($transaction);
                $this->getEntityManager()->flush($transaction);

                $dwlNeedsToGenerateFile = [];
                $differenceBalance = (new Number($oldAmount))->minus($subTransaction->getAmount());
                $this->updateDWLItemBalance($dwlItem->getCustomerProduct(), $dwl->getDate(), $differenceBalance, $dwlNeedsToGenerateFile);

                foreach ($dwlNeedsToGenerateFile as $dwlId) {
                    $this->runProcessForGenerateFile($dwlId);
                }
                
                if ($transaction->getCustomer()->hasReferrer()) {
                    $referrer = $transaction->getCustomer()->getReferrer();
                    $period = $this->getCommissionPeriodRepository()->getCommissionForDWL($dwl);
                    if ($period instanceof CommissionPeriod) {
                        $computeJob = new Job('commission:period:compute',
                            [
                                $this->getUser()->getUsername(),
                                '--period',
                                $period->getId(),
                                '--member',
                                $referrer->getId(),
                                '--env',
                                $this->getContainer()->get('kernel')->getEnvironment(),
                            ],
                            true,
                            'payout'
                        );
                        $this->getEntityManager()->persist($computeJob);
                        $this->getEntityManager()->flush($computeJob);
                    }
                }
            }
            $this->saveDWLFile($dwl, $data);
            $this->getRepository()->commit();
            
            return $item;
        } catch (\Exception $e) {
            $this->getRepository()->rollback();

            throw $e;
        }
    }

    /**
     * Save dwl file.
     *
     * @param DWL $dwl
     * @param array                $data
     * @param bool                 $updateProgress
     */
    public function saveDWLFile($dwl, $data, $updateProgress = true)
    {
        $filesystem = new Filesystem();

        $fileName = sprintf('%s%s_v_%s.json', $this->getMediaManager()->getPath('dwl'), $dwl->getId(), $dwl->getVersion());
        file_put_contents($fileName, json_encode($data));

        if ($updateProgress) {
            $fileName = sprintf('%sprogress_%s_v_%s.json', $this->getMediaManager()->getPath('dwl'), $dwl->getId(), $dwl->getVersion());
            if (!$filesystem->exists($fileName)) {
                $filesystem->touch($fileName);
            }
            $updatedAt = base64_encode($dwl->getUpdatedAt()->format('Y-m-d H:i:s'));
            file_put_contents(
                $fileName,
                json_encode([
                    '_v' => $updatedAt,
                    'status' => $dwl->getStatus(),
                    'process' => $data['total']['record'],
                    'total' => $data['total']['record'],
                ])
            );
        }
    }

    public function updateDWLItemBalance(CustomerProduct $customerProduct, DateTime $uploadDate, $differentBalance = 0, &$dwlIds = []): void
    {
        $dwlList = $this->getRepository()->findDWL(['from' => $uploadDate->format('Y-m-d') . '+1 day']);
        foreach ($dwlList as $dwl) {
            $dwlIds[] = $dwl->getId();
            $subTransaction = $this->getDWLItemSubtransaction($dwl, $customerProduct);
            if ($subTransaction !== null) {
                $currenctBalance = new Number($subTransaction->getDetail('dwl.customer.balance'));

                $subTransaction->setDetail('dwl.customer.balance', $currenctBalance->minus($differentBalance)->__toString());
                $this->save($subTransaction);
            }
        }
    }

    public function getDWLItemSubtransaction(DWL $dwl, CustomerProduct $customerProduct): ?SubTransaction
    {
        return $this->getDWLTransactionRepository()->getSubtransactionByProductAndDwlId($customerProduct->getId(), $dwl->getId());
    }

    public function runProcessForGenerateFile(int $dwlId)
    {
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $command = [
            $this->getContainer()->getParameter('php_command'),
            $rootDir . "/console",
            'dwl:generate:file',
            $dwlId,
            '&',
        ];

        $process = new Process(implode(' ', $command));
        $process->start();
    }

    public function exportToCsv(int $dwlId, $betadminToSync = false): string
    {
        $subtransactions = $this->getDWLTransactionRepository()->getSubtransactionsByDWLId($dwlId, null, null);

        $rows = 'Username,Turnover,Gross Commission,Member Commission,Total Amount,';
        $rows .= ($betadminToSync ? 'Customer Available Balance' : 'Customer Balance Before the Submission');

        /* @var $subtransaction SubTransaction */
        foreach ($subtransactions as $subtransaction) {
            $rows .= PHP_EOL;
            $rows .= sprintf(
                "%s,%s,%s,%s,%s,%s",
                $subtransaction->getCustomerProduct()->getUserName(),
                $subtransaction->getDetail('dwl.turnover'),
                $subtransaction->getDetail('dwl.gross'),
                $subtransaction->getDetail('dwl.commission'),
                $subtransaction->getAmount(),
                $subtransaction->getDetail('dwl.customer.balance')
            );
        }

        return $rows;
    }

    /**
     * Get dwl repository.
     *
     * @return DWLRepository
     */
    public function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:DWL');
    }

    /**
     * Get media manager.
     *
     * @return MediaManager
     */
    protected function getMediaManager()
    {
        return $this->getContainer()->get('media.manager');
    }

    /**
     * Get customer product repository.
     *
     * @return CustomerProductRepository
     */
    protected function getCustomerProductRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerProduct');
    }

    /**
     * Get transaction repository.
     *
     * @return TransactionRepository
     */
    protected function getTransactionRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Transaction');
    }

    protected function getSubTransactionRepository(): SubTransactionRepository
    {
        return $this->getDoctrine()->getRepository(SubTransaction::class);
    }

    protected function getDWLTransactionRepository(): TransactionRepository2
    {
        return $this->getContainer()->get('dwl.transaction_repository');
    }

    protected function getTransactionManager()
    {
        return $this->getContainer()->get('transaction.manager');
    }

    protected function getSettingManager()
    {
        return $this->getContainer()->get('app.setting_manager');
    }
    
    private function getCommissionManager(): CommissionManager
    {
        return $this->getContainer()->get('commission.manager');
    }
    
    private function getCommissionPeriodRepository(): CommissionPeriodRepository
    {
        return $this->getDoctrine()->getRepository(CommissionPeriod::class);
    }
    
    private function getRootDirectory(): string
    {
        return $this->getContainer()->get('kernel')->getRootDir();
    }
}
