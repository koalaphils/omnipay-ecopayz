<?php

namespace MemberBundle\Manager;

use DateTimeInterface;
use AppBundle\Manager\AbstractManager;
use AppBundle\Manager\SettingManager;
use AppBundle\Widget\Page\ListWidget;
use AppBundle\ValueObject\Number;
use CommissionBundle\Manager\CommissionManager;
use CurrencyBundle\Manager\CurrencyManager;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\User as MemberUser;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\MemberCommission;
use DbBundle\Entity\MemberRunningCommission;
use DbBundle\Entity\MemberRevenueShare;
use DbBundle\Entity\Product;
use DbBundle\Entity\ProductCommission;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\AuditRevision;
use DbBundle\Entity\AuditRevisionLog;
use DbBundle\Entity\Currency;
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
use DbBundle\Repository\MemberRevenueShareRepository;
use DbBundle\Repository\CurrencyRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use MemberBundle\Event\ReferralEvent;
use MemberBundle\Events;
use PinnacleBundle\Service\PinnacleService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;
use TransactionBundle\Manager\TransactionManager;
use PinnacleBundle\Component\Model\WinlossResponse;
use AppBundle\ValueObject\Money;
use DateTimeImmutable;

class MemberManager extends AbstractManager
{
    private $translator;
    private $entityManager;
    private $settingManager;
    private $eventDispatcher;
    private $transactionManager;
    private $precision;
    private $commissionManager;
    private $memberProductRepository;
    private $memberReferralNameRepository;
    private $pinnacleService;

    public function __construct(
        PinnacleService $pinnacleService,
        SettingManager $settingManager
    ){
        $this->pinnacleService = $pinnacleService;
        $this->settingManager = $settingManager;
    }

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

    public function getDateTo($schemas, $key, $lastKey, $filters)
    {   
        if ($key == $lastKey) {
            return $filters['dwlDateTo'];
        } else {
            $key += 1;
            $date = \DateTime::createFromFormat('Y-m-d', $schemas[$key]['createdAt']);
            $date->modify('-1 day');

            return $date->format('Y-m-d');
        }   
    }

    public function getTurnoversAndCommissionsByMember(int $referrerId, array $filters): array
    {
        $memberRepository = $this->getRepository();
        $referrerDetails = $memberRepository->findById($referrerId);
        $filters['hideZeroTurnover'] = array_get($filters, 'hideZeroTurnover', false);
        $filters['startDate'] = $this->getSettingManager()->getSetting('commission.startDate');

        $result = [
            'limit' => $filters['limit'],
            'page' => $filters['page'],
            'filters' => $filters,
            'records' => [],
            'allRecords' => [],
            'recordsFiltered' => $memberRepository->getReferralProductListFilterCountByReferrer($filters, $referrerId),
            'recordsTotal' => $memberRepository->getReferralProductListTotalCountByReferrer($filters, $referrerId),
        ];

        $orders = [
            ['column' => $filters['orderBy'], 'dir' => 'ASC'],
            ['column' => $filters['orderBy'] == 'productName' ? 'memberId' : 'productName', 'dir' => 'ASC'],
        ];

        $membersAndProducts = $memberRepository->getAllReferralProductListByReferrer(
                $filters, $orders, $referrerId, $filters['offset'], $filters['limit']
            );

        $this->precision = isset($filters['precision']) ? $filters['precision'] : 2;
        $lookupPinBet = array_search(MemberRevenueShare::PINNACLE_PRODUCT_ID, array_column($membersAndProducts, 'productId'));

        $schemas = [];
        if (!is_null($lookupPinBet)) {
            $schemas = $this->getMemberRevenueShareRepository()->findSchemeByRange($referrerId, MemberRevenueShare::PINNACLE_PRODUCT_ID, $filters);
            $schemaCount = count($schemas);
            $lastKey = $schemaCount - 1;
        }

        $subTransactionRepository = $this->getSubTransactionRepository();
        $offset = $filters['offset'];
        $displayCount = 0;
        $recordCount = 0;
        foreach ($membersAndProducts as &$row) {
            $recordCount += 1;
            $totalBonus = 0;
            $totalWinLoss = 0;
            $totalTurnover = 0;
            $totalRevenueShare = 0;

            $memberRecord = [
                'productId' => $row['productId'],
                'productName' => $row['productName'],
                'memberId' => $row['memberId'],
                'memberProductId' => $row['memberProductId'],
                'currencyCode' => $row['currencyCode'],
                'totalBonus' => $this->formatCommissionAmount($totalBonus),
                'totalWinLoss' => $this->formatCommissionAmount($totalWinLoss),
                'totalTurnover' => $this->formatCommissionAmount($totalTurnover),
                'totalRevenueShare' => $this->formatCommissionAmount($totalRevenueShare)
            ];

            foreach ($schemas as $key => $schema) {
                $bonus = 0;
                $winLoss = 0;
                $turnover = 0;
                $revenueShare = 0;

                $newFrom = $schema['createdAt'];
                $newTo = $this->getDateTo($schemas, $key, $lastKey, $filters);
                if ($key == 0) {
                    if ($filters['dwlDateFrom'] > $schema['createdAt']) {
                        $newFrom = $filters['dwlDateFrom'];
                    }
                } else if ($key == $lastKey) {
                    $newTo = $filters['dwlDateTo'];
                }

                // Get Pinnacle Data
                $pinnacleData = $this->pinnacleService->getReportComponent()->winLoss($row['pinUserCode'], $newFrom, $newTo);
                if ($pinnacleData instanceof WinLossResponse) {
                    $winLoss = $pinnacleData->getTotalDetail('payout');
                    $turnover = $pinnacleData->getTotalDetail('turnover');
                    $totalWinLoss += $winLoss;
                    $totalTurnover += $turnover;
                }

                $filters['bonusDateFrom'] = $newFrom;
                $filters['bonusDateTo'] = $newTo;

                // Get Transaction Bonus
                $memberBonus = $subTransactionRepository->getBonusByMember($filters, $orders, $row['memberId']);
                if ($memberBonus){
                    $bonus = $memberBonus['totalBonus'];
                    $totalBonus += $bonus;
                }
                
                $revenueShare = $this->getRevenueShare($schema, $winLoss, $bonus, $row['currencyCode'], $referrerDetails->getCurrencyCode());
                $totalRevenueShare += $revenueShare;
            }

            $memberRecord['totalWinLoss'] = $this->formatCommissionAmount($totalWinLoss);
            $memberRecord['totalTurnover'] = $this->formatCommissionAmount($totalTurnover);
            $memberRecord['totalBonus'] = $this->formatCommissionAmount($totalBonus);
            $memberRecord['totalRevenueShare'] = $this->formatCommissionAmount($totalRevenueShare);
            $result['allRecords'][] = $memberRecord;

            // Filter hideZeroTurnover
            if (!(array_get($filters, 'hideZeroTurnover') && ($totalTurnover == 0))) {
                // Display Data
                if (($offset < $recordCount) && ($displayCount < $filters['limit'])){
                    $displayCount += 1;
                    $result['records'][] = $memberRecord;
                }
            }
        }


        $currencyPerRecord = array_column($result['allRecords'], 'currencyCode');
        $currencies = array_unique($currencyPerRecord);
        $curreciesRecords = [];

        foreach ($currencies as $currency) {
            $totalWinLoss = new Number(0);
            $totalTurnover = new Number(0);
            $totalAffiliateBonus = new Number(0);
            $totalAffiliateRevenueShare = new Number(0);
            
            $perCurrency['currencyCode'] = $currency;
            $perCurrency['totalWinLoss'] = $this->formatCommissionAmount(0);
            $perCurrency['totalTurnover'] = $this->formatCommissionAmount(0);
            $perCurrency['totalAffiliateBonus'] = $this->formatCommissionAmount(0);
            $perCurrency['totalAffiliateRevenueShare'] = $this->formatCommissionAmount(0);

            //Get record indexes that has match currency
            $matchCurrency = array_filter($currencyPerRecord, function($k) use ($currency) {
                return $k == $currency;
            });

            foreach ($matchCurrency as $key => $data) {
                $totalWinLoss = $totalWinLoss->plus($result['allRecords'][$key]['totalWinLoss']['original']);
                $totalTurnover = $totalTurnover->plus($result['allRecords'][$key]['totalTurnover']['original']);
                $totalAffiliateBonus = $totalAffiliateBonus->plus($result['allRecords'][$key]['totalBonus']['original']);
                $totalAffiliateRevenueShare = $totalAffiliateRevenueShare->plus($result['allRecords'][$key]['totalRevenueShare']['original']);
            }
            
            $perCurrency['totalWinLoss'] = $this->formatCommissionAmount($totalWinLoss);
            $perCurrency['totalTurnover'] = $this->formatCommissionAmount($totalTurnover);
            $perCurrency['totalAffiliateRevenueShare'] = $this->formatCommissionAmount($totalAffiliateRevenueShare);
            $perCurrency['totalAffiliateBonus'] = $this->formatCommissionAmount($totalAffiliateBonus);
            $curreciesRecords[] = $perCurrency;
        }

        $result['totals'] = $curreciesRecords;
        $result['period'] = [
            'dwlDateFrom' => $filters['dwlDateFrom'],
            'dwlDateTo' => $filters['dwlDateTo'],
        ];

        return $result;
    }

    public function getRevenueShare(array $schema, int $winLoss, int $bonus, string $fromCurrencyCode, string $toCurrencyCode)
    {
        $settings = json_decode($schema['revenueShareSettings'], true);
        $totalRevenueShare = 0;
        $absDwl = abs($winLoss);
        $settingsCount = 0;
        if ($settings){
            $settingsCount = count($settings);
        }

        /*Temporarily, there is only 1 range per scheme as discussed with Ronnie*/
        for($x=0; $x<$settingsCount; $x++) {
            if ($settings[$x]["min"] <= $absDwl) {
                $percentage = Number::div($settings[$x]["percentage"], 100)->toString();
                $winLossMinBonus = Number::add($winLoss, $bonus)->toString();
                $revenueShare = Number::mul(-1, $winLossMinBonus)->times($percentage)->toString();
                $convertedTotalRevenueShare = $this->getConvertedCurrencyRate($schema['createdAt'], $fromCurrencyCode, $toCurrencyCode, $revenueShare);
                $totalRevenueShare = $convertedTotalRevenueShare[$toCurrencyCode];
            }
        }

        return $totalRevenueShare;
    }

    public function getConvertedCurrencyRate(string $transactionDate, string $fromCurrencyCode, string $toCurrencyCode, string $totalRevenueShare): array
    {   
        $transactionDate = new \DateTimeImmutable($transactionDate, new \DateTimeZone('UTC'));
        $transactionDate = $transactionDate->format('Y-m-d');
        $currencyDateRate = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            $transactionDate . ' 23:59:59'
        );

        $fromCurrency = $this->getCurrencyRepository()->findOneBy(['code' => $fromCurrencyCode]);
        $toCurrency = $this->getCurrencyRepository()->findOneBy(['code' => $toCurrencyCode]);

        $currencyRate = $convertionRate = $this
                ->getCurrencyManager()
                ->getConvertionRate($fromCurrency, $toCurrency, $currencyDateRate);

        $commission = new Money(
            $convertionRate->getSourceCurrency(),
            $totalRevenueShare
        );

        $computed[$commission->getCurrencyCode()] = $commission->getAmount();
        $computed['original'] = $commission->getAmount();

        if (!$convertionRate->getSourceCurrency()->isEqualTo($convertionRate->getDestinationCurrency())) {
            $convertedCommission = $commission->convertToCurrency(
                $convertionRate->getDestinationCurrency(),
                $convertionRate->getSourceRate(),
                $convertionRate->getDestinationRate()
            );
            $computed[$convertedCommission->getCurrencyCode()] = $convertedCommission->getAmount();
            $computed['original'] = $convertedCommission->getAmount();
        }

        return $computed;
    }

    public function generateTurnoverCommissionReport(int $referrerId, array $filters): void
    {
        $separator = ',';
        $member = $this->getRepository()->find($referrerId);
        $memberCurrencyCode = $member->getCurrencyCode();
        $allowRevenueShare = $member->isRevenueShareEnabled();

        $filters['limit'] = $this->getRepository()->getReferralProductListTotalCountByReferrer($filters, $referrerId);
        $filters['offset'] = 0;
        $result = $this->getTurnoversAndCommissionsByMember($referrerId, $filters);

        $dateFrom = new DateTimeImmutable($result['period']['dwlDateFrom']);
        $result['period']['dwlDateFrom'] = $dateFrom->format('Y-m-d');

        $dateTo = new DateTimeImmutable($result['period']['dwlDateTo']);
        $result['period']['dwlDateTo'] = $dateTo->format('Y-m-d');

        $csvReport = '';
        $csvReport .= 'Member: ' . $member->getFullName() . ' (' . $member->getUsername() . ')' . "\n\n";
        echo $csvReport;

        $csvReport = '';
        $csvReport .= $filters['orderBy'] == 'productName' ? 'Product, Member ID' : 'Member ID, Product';
        
        if ($allowRevenueShare) {
            $csvReport .= ', Total Turnover,  Total W/L, Total Bonus, Affiliate Revenue Share';  
        } else {
            $csvReport .= ', Total Turnover,  Total W/L';
        }
        
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
                
                if ($allowRevenueShare) {
                    $csvReport .= '"' . $memberCurrencyCode . ' ' . $record['totalBonus']['rounded']  . '"' . $separator;
                    $csvReport .= '"' . $memberCurrencyCode . ' ' . $record['totalRevenueShare']['rounded']  . '"' . $separator;
                }
            } else {
                $csvReport .= 'No products' . $separator . $separator . $separator . $separator;
            }

            $csvReport .= "\n";

            echo $csvReport;
        }

        $totalTurnover = [];
        $totalWinLoss = [];
        $totalBonus = []; 
        $totalRevenueShare = [];

        foreach ($result['totals'] as $total) {
            $currencyCode = $total['currencyCode'];
            $totalTurnover[$currencyCode] = $total['totalTurnover'];
            $totalWinLoss[$currencyCode] = $total['totalWinLoss'];
            $totalAffiliateBonus[$currencyCode] = $total['totalAffiliateBonus'];
            $totalAffiliateRevenueShare[$currencyCode] = $total['totalAffiliateRevenueShare'];
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

        if ($allowRevenueShare) {
            $csvReport .= '"';
            foreach ($totalAffiliateBonus as $currencyCode => $total) {
                $csvReport .= $currencyCode . ' ' . $total['rounded'] . "\n";
            }
            $csvReport .= '",';
        }

        if ($allowRevenueShare) {
            $csvReport .= '"';
            $csvReport .= $memberCurrencyCode . ' ' . $totalAffiliateRevenueShare[$memberCurrencyCode]['rounded'] . "\n";
            $csvReport .= '"';
        }

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

    public function setCurrencyManager(CurrencyManager $currencyManager): void
    {
        $this->currencyManager = $currencyManager;
    }

    public function setMemberProductRepository(MemberProductRepository $memberProductRepository): void
    {
        $this->memberProductRepository = $memberProductRepository;
    }

    public function setMemberPaymentOptionRepository(MemberPaymentOptionRepository $memberPaymentOptionRepository): void
    {
        $this->memberPaymentOptionRepository = $memberPaymentOptionRepository;
    }

    public function setCurrencyRepository(CurrencyRepository $currencyRepository): void
    {
        $this->currencyRepository = $currencyRepository;
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

    public function getCurrencyManager(): CurrencyManager
    {
        return $this->currencyManager;
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

    private function getMemberRevenueShareRepository(): MemberRevenueShareRepository
    {
        return $this->getDoctrine()->getRepository(MemberRevenueShare::class);
    }

    private function getCurrencyRepository(): CurrencyRepository
    {
        return $this->getDoctrine()->getRepository(Currency::class);
    }
}