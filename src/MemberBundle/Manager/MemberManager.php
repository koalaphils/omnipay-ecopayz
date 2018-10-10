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
use DbBundle\Repository\CustomerProductRepository as MemberProductRepository;
use DbBundle\Repository\CustomerRepository as MemberRepository;
use DbBundle\Repository\MemberCommissionRepository;
use DbBundle\Repository\MemberRunningCommissionRepository;
use DbBundle\Repository\ProductCommissionRepository;
use DbBundle\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use DbBundle\Repository\SubTransactionRepository;
use DbBundle\Repository\AuditRevisionRepository;
use AppBundle\ValueObject\Number;

class MemberManager extends AbstractManager
{
    private $settingManager;

    public function __construct(SettingManager $settingManager)
    {
        $this->settingManager = $settingManager;
    }

    private $commissionManager;
    private $memberProductRepository;

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

    public function getOriginSetting(): array
    {
        return $this->settingManager->getSetting('origin.origins');
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

        $acWallet = $this->getProductRepository()->getAcWalletProduct();
        $filters['acWalletProductId'] = $acWallet->getId();
        $turnoversWinLossCommissions = $this->getTurnoversAndCommissionsByMember($referrerId, $filters);

        return $turnoversWinLossCommissions;
    }

    public function getTurnoversAndCommissionsByMember(int $referrerId, array $filters): array
    {
        $result = [
            'records' => $this->getRepository()
                ->getReferralProductListByReferrer(
                    $filters,
                    [['column' => $filters['orderBy'], 'dir' => 'ASC']],
                    $referrerId, $filters['offset'], $filters['limit']
                ),
            'recordsTotal' => $this->getRepository()->getReferralProductListTotalCountByReferrer($filters, $referrerId),
            'recordsFiltered' => $this->getRepository()->getReferralProductListFilterCountByReferrer($filters, $referrerId),
            'limit' => $filters['limit'],
            'page' => $filters['page'],
            'filters' => $filters,
        ];

        $filters['memberProductIds'] = array_column($result['records'], 'memberProductId');
        $filters['startDate'] = $this->getSettingManager()->getSetting('commission.startDate');

        $memberReferralsTurnoversAndCommissions = array_column(
            $this->getSubTransactionRepository()->getReferralTurnoverWinLossCommissionByReferrer($filters, $referrerId),
            null,
            'memberProductId'
        );

        foreach ($result['records'] as &$row) {
            $row = array_merge($row, [
                'totalTurnover' => 0,
                'totalWinLoss' => 0,
                'totalAffiliateCommission' => 0,
            ]);

            if (array_has($memberReferralsTurnoversAndCommissions, $row['memberProductId'])) {
                $row = array_get($memberReferralsTurnoversAndCommissions, $row['memberProductId']);
            }
        }

        $currencies = $this->getMemberProductRepository()->getReferralCurrencies($referrerId);
        $totals = array_column(
            $this
            ->getSubTransactionRepository()
            ->getTotalReferralTurnoverWinLossCommissionByReferrer($filters, $referrerId),
            null,
            'currencyCode'
        );

        $totalAffiliateCommission = new Number(0);
        foreach ($totals as $total) {
            $totalAffiliateCommission = $totalAffiliateCommission->plus($total['totalAffiliateCommission']);
        }

        foreach ($currencies as &$currency) {
            $currency['totalTurnover'] = 0;
            $currency['totalWinLoss'] = 0;

            if (array_has($totals, $currency['currencyCode'])) {
                $currency = array_get($totals, $currency['currencyCode']);
            }

            $currency['totalAffiliateCommission'] = $totalAffiliateCommission->toString();
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
        $numberConfig = ['precision' => 2, 'round' => false];
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
                $csvReport .= $record['productName'] . $separator;
            }

            $csvReport .= '"' . $record['currencyCode'] . ' ' . number_format(Number::format($record['totalTurnover'], $numberConfig), 2) . '"' . $separator;
            $csvReport .= '"' . $record['currencyCode'] . ' ' . number_format(Number::format($record['totalWinLoss'], $numberConfig), 2)  . '"' . $separator;
            $csvReport .= '"' . $memberCurrencyCode . ' ' . number_format(Number::format($record['totalAffiliateCommission'], $numberConfig), 2)  . '"' . $separator;
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
            $csvReport .= $currencyCode . ' ' . number_format(Number::format($total, $numberConfig), 2) . "\n";

        }
        $csvReport .= '",';

        $csvReport .= '"';
        foreach ($totalWinLoss as $currencyCode => $total) {
            $csvReport .= $currencyCode . ' ' . number_format(Number::format($total, $numberConfig), 2) . "\n";
        }
        $csvReport .= '",';

        $csvReport .= '"';
        $csvReport .= $memberCurrencyCode . ' ' . number_format(Number::format($totalAffiliateCommission[$memberCurrencyCode], $numberConfig), 2) . "\n";
        $csvReport .= '"';

        echo $csvReport;
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

    protected function getRepository(): MemberRepository
    {
        return $this->getDoctrine()->getRepository(Member::class);
    }
    
    protected function getAuditRevisionRepository(): AuditRevisionRepository
    {
        return $this->getDoctrine()->getRepository(AuditRevision::class);
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

    private function getMemberProductRepository(): MemberProductRepository
    {
        return $this->memberProductRepository;
    }
}
