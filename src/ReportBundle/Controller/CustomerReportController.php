<?php

namespace ReportBundle\Controller;

use AppBundle\Controller\AbstractController;
use DbBundle\Entity\Currency;
use DbBundle\Entity\Customer;
use DbBundle\Entity\Product;
use DbBundle\Repository\CurrencyRepository;
use DbBundle\Repository\CustomerRepository;
use DbBundle\Repository\ProductRepository;
use ReportBundle\Manager\ReportCustomerManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Description of CustomerReportController
 *
 * @author Cydrick Nonog <cydrick.dev@gmail.com>
 */
class CustomerReportController extends AbstractController
{
    public function indexAction()
    {
        // off this feature
        return $this->redirectToRoute("app.dashboard_page");
        
        $this->denyAccessUnlessGranted(['ROLE_REPORT_CUSTOMER_VIEW']);
        $this->getSession()->save();
        $currencies = $this->getCurrencyRepository()->getCurrencyList();

        return $this->render('ReportBundle:Report:customers/customers.html.twig', ['currencies' => $currencies]);
    }

    // Member Reports Page Ajax for List
    public function currencyReportAction(Request $request, string $currencyCode): Response
    {
        $filters = $request->get('filters');
        if (empty($filters['from'] ?? null) || empty($filters['to'] ?? null)) {
            throw new HttpException(Response::HTTP_BAD_REQUEST);
        }

        $currency = $this->getCurrencyRepository()->findOneBy(['code' => $currencyCode]);
        $reportStartDate = \DateTimeImmutable::createFromFormat('m/d/Y', $filters['from']);
        $reportEndDate = \DateTimeImmutable::createFromFormat('m/d/Y', $filters['to']);


        $customerNameQueryString = array_get($filters, 'customer_search', null);
        $hideZeroValueRecords = $filters['hideZeroValueRecords'] ?? false;

        $dwlReportsPerMember = $this->getManager()->getMemberDwlReports(
            $currency,
            $reportStartDate,
            $reportEndDate,
            $customerNameQueryString,
            null,
            $hideZeroValueRecords,
            null,
            null
        );

        if (empty($dwlReportsPerMember)) {
            return $this->zTableResponse();
        }
        $allDwlReportsPerMember = $this->getManager()->getMemberDwlReports(
            $currency,
            $reportStartDate,
            $reportEndDate,
            null,
            null,
            $hideZeroValueRecords
        );
        $report['records'] = $this->addLinksToMemberReportPage($dwlReportsPerMember, $request);
        $report['recordsTotal'] = count($allDwlReportsPerMember);
        $report['recordsFiltered'] = $report['recordsTotal'];
        if (!empty($customerNameQueryString)) {
            $allFilteredDwlReportsPerMember = $this->getManager()->getMemberDwlReports(
                $currency,
                $reportStartDate,
                $reportEndDate,
                $customerNameQueryString,
                null,
                $hideZeroValueRecords
            );
            $report['recordsFiltered'] = count($allFilteredDwlReportsPerMember);
        }


        return $this->zTableResponse($report);
    }

    // Member DWL Reports
    public function exportCustomersAction(Request $request, $currency): Response
    {
        $filters = $request->get('filters', []);
        $currencyEntity = $this->getEntityManager()->getRepository(Currency::class)->findOneByCode($currency);
        $reportStartDate =  new \DateTimeImmutable($filters['from']);
        $reportEndDate = new \DateTimeImmutable($filters['to']);
        $customerNameQueryString = array_get($filters, 'customer_search', null);
        $hideInactiveMembers = ($filters['hideInactiveMembers'] == true);
        $hideZeroValueRecords = $filters['hideZeroValueRecords'] ?? false;

        $response = new StreamedResponse(function () use ($currencyEntity, $reportStartDate, $reportEndDate, $customerNameQueryString, $hideInactiveMembers, $hideZeroValueRecords) {
            $this->getManager()->printMemberDwlReports(
                $currencyEntity,
                $reportStartDate,
                $reportEndDate,
                $customerNameQueryString,
                null,
                $hideInactiveMembers,
                $hideZeroValueRecords
            );

        });

        $filename =  'CustomersReport_'. $currencyEntity->getCode() .'_'. $reportStartDate->format('Ymd') .'_'. $reportEndDate->format('Ymd') .'.csv';
        $this->setResponseTypeAsCSVFile($response, $filename);

        return $response;
    }


    /**
     * @return array [
            "dwl" => [
                "turnover" => float
                "winLoss" => float
                "gross" => float
                "commission" => float
                "amount" => float
            ],
            "customer" => [
                "totalCustomerProduct" => float
                "totalCustomer" => float
                "totalBalances" => float
                "totalBalanceUpToEndOfReportDateRange" => float
            ]
        ]
     **/
    private function addCurrencyReportTotals(Currency $currency, array $filters): array
    {
        $totals = [];
        // TODO; AC6-1062: temporarily disabled until speed issue is fixed
        // $totals  = $this->getManager()->getReportCustomerTotal($currency, new \DateTimeImmutable($filters['from']), new \DateTimeImmutable($filters['to']), array_get($filters, 'customer_search', null));
        return $totals;
    }

    private function addLinksToMemberReportPage(array $reportData, Request $request): array
    {
        $reportData = array_map(
            function ($customer) use ($request) {
                $customer['link'] = $this->getRouter()->generate(
                    'report.customer',
                    [
                        'currency' => $customer['currency_code'],
                        'customerId' => $customer['customer_id'],
                        'filters' => $request->get('filters', []),
                    ]
                );

                return $customer;
            },
            $reportData
        );

        return $reportData;
    }

    public function memberReportSummaryAction(Request $request, $currency): Response
    {
        //todo: apply the totals for filters, but not on pagination
        // todo: cancel pending ajax if filters or dates have been changed
        if ($request->get('export', false) && $this->has('profiler')) {
            $this->get('profiler')->disable();
        }
        set_time_limit(0);
        $this->getSession()->save();
        $filters = $request->get('filters');
        $currency = $this->getCurrencyRepository()->findOneBy(['code' => $currency]);
        $filters['currency'] = $currency->getId();

        if (array_has($filters, 'from')) {
            $filters['from'] = \DateTime::createFromFormat('m/d/Y', $filters['from'])->format('Y-m-d');
        }
        if (array_has($filters, 'to')) {
            $filters['to'] = \DateTime::createFromFormat('m/d/Y', $filters['to'])->format('Y-m-d');
        }

        $report['totalSummary'] = $this->getManager()->getReportCustomerTotal($currency, new \DateTimeImmutable($filters['from']), new \DateTimeImmutable($filters['to']), array_get($filters, 'customer_search', null));

        return $this->jsonResponse($report);
    }

    public function customerPageAction(Request $request, $currency, $customerId): Response
    {
        $this->getSession()->save();
        $currency = $this->getCurrencyRepository()->findOneBy(['code' => $currency]);
        $customer = $this->getCustomerRepository()->find($customerId);

        $filters = $request->get('filters', []);
        $reportFilters = $filters;
        if (empty($reportFilters['from'] ?? null) || empty($reportFilters['to'] ?? null)) {
            throw new HttpException(Response::HTTP_BAD_REQUEST);
        }

        $reportStartDate = \DateTimeImmutable::createFromFormat('m/d/Y', $reportFilters['from']);
        $reportEndDate = \DateTimeImmutable::createFromFormat('m/d/Y', $reportFilters['to']);

        $reportFilters['customer'] = $customer->getId();

        $limit = 1;
        $page = 1;
        $customerReport = $this->getManager()->getMemberDwlReports(
            $currency,
            $reportStartDate,
            $reportEndDate,
            null,
            $customerId,
            false,
            $limit,
            $page
        );

        $memberDwlReportsPerMemberProduct = $customerReport[0];

        return $this->render('ReportBundle:Report:customers/customer.html.twig', [
            'currency' => $currency,
            'customer' => $customer,
            'filters' => $filters,
            'report' => $memberDwlReportsPerMemberProduct,
        ]);
    }


    public function customerProductsReportAction(Request $request): Response
    {
        $this->getSession()->save();
        if ($request->get('export', false) && $this->has('profiler')) {
            $this->get('profiler')->disable();
        }

        $filters = $request->get('filters', []);
        $totalFilters = [];
        if (array_has($filters, 'from')) {
            $reportStartDate = \DateTimeImmutable::createFromFormat('m/d/Y', $filters['from']);
            $filters['from'] = $reportStartDate->format('Y-m-d');
            $totalFilters['from'] = $filters['from'];
        }
        if (array_has($filters, 'to')) {
            $reportEndDate = \DateTimeImmutable::createFromFormat('m/d/Y', $filters['to']);
            $filters['to'] = $reportEndDate->format('Y-m-d');
            $totalFilters['to'] = $filters['to'];
        }
        if (array_has($filters, 'customer')) {
            $totalFilters['customerID'] = $filters['customer'];
            $totalFilters['customer'] = $filters['customer'];
        }
        if (array_has($filters, 'currency')) {
            $totalFilters['currency'] = $filters['currency'];
            $totalFilters['currencies'] = [$filters['currency']];
        }

        $productId = $filters['products'][0] ?? null;
        if (array_has($filters, 'products')) {
            $totalFilters['products'] = $filters['products'];
            $product = $this->getEntityManager()->getRepository(Product::class)->findOneById($productId);
            $filters['isForSkypeBetting'] =  $product->isSkypeBetting();
            $filters['limit'] = $request->get('limit', 20);
        }

        if (array_has($filters, 'search')) {
            $totalFilters['search'] = $filters['search'];
        }

        $report = $this->getManager()->getCustomerProductList($filters, $request->get('limit', 20), $request->get('page', 1));
        $reportSummary = $this->getManager()->getMemberProductsReportSummary(
            $productId,
            $filters['currency'],
            $reportStartDate,
            $reportEndDate,
            $filters['search'],
            $filters['hideZeroValueRecords'] ?? false
        );

        $report['totalSummary']['turnover'] = $reportSummary['turnOverTotal'];
        $report['totalSummary']['gross_commission'] = $reportSummary['grossCommissionTotal'];
        $report['totalSummary']['win_loss'] = $reportSummary['winlossTotal'];
        $report['totalSummary']['commission'] = $reportSummary['commissionTotal'];
        $report['totalSummary']['availableBalaneAsOfReportEndDateTotal'] = $reportSummary['availableBalaneAsOfReportEndDateTotal'];
        $report['totalSummary']['currentBalanceTotal'] = $reportSummary['currentBalanceTotal'];

        return $this->jsonResponse($report);
    }

    // csv export for ALL member products
    public function exportCustomerProductsAction(Request $request): Response
    {
        $filters = $request->get('filters', []);
        $currencyEntity = $this->getEntityManager()->getRepository(Currency::class)->findOneByid($filters['currency']);
        $reportIsZeroValue = array_get($filters, 'hideZeroValueRecords', false);
        $reportStartDate =  new \DateTimeImmutable($filters['from']);
        $reportEndDate = new \DateTimeImmutable($filters['to']);
        $memberProductUsernameQueryString = array_get($filters, 'search', null);

        $product = $this->getProductRepository()->find($filters['products'][0]);
        $response = new StreamedResponse(function () use ($product, $currencyEntity, $reportStartDate, $reportEndDate, $memberProductUsernameQueryString, $reportIsZeroValue) {
                    $this->getManager()->printMemberProductsCsvReport($product, $currencyEntity, $reportStartDate, $reportEndDate, $memberProductUsernameQueryString, $reportIsZeroValue);
        });
        $filename =  $this->getMemberProductsReportFilename($product, $currencyEntity, $reportStartDate, $reportEndDate);
        $this->setResponseTypeAsCSVFile($response, $filename);

        return $response;
    }

    private function getMemberProductsReportFilename(Product $product, Currency $currency, \DateTimeInterface $startDate, \DateTimeInterface $endDate): String
    {
        return $product->getName() . '_' . $currency->getCode() . '_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.csv';
    }

    // csv export for ALL member products of a specific member
    public function exportCustomerProductsByMemberAction(Request $request, int $memberId): Response
    {
        $filters = $request->get('filters', []);
        $reportStartDate =  new \DateTimeImmutable($filters['from']);
        $reportEndDate = new \DateTimeImmutable($filters['to']);
        $memberProductUsernameQueryString = array_get($filters, 'search', null);
        $member = $this->getRepository(Customer::class)->find($memberId);

        $response = new StreamedResponse(function() use  ($member, $reportStartDate, $reportEndDate, $memberProductUsernameQueryString) {
            $this->getManager()->printMemberProductsByMemberCsvReport($member, $reportStartDate, $reportEndDate, $memberProductUsernameQueryString);
        });

        $filename =  $this->getMemberProductsReportByMemberFilename($member, $reportStartDate, $reportEndDate);
        $this->setResponseTypeAsCSVFile($response, $filename);

        return $response;
    }

    private function getMemberProductsReportByMemberFilename( $member, \DateTimeInterface $startDate, \DateTimeInterface $endDate): String
    {
        return $member->getFullName() . '_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.csv';
    }

    protected function getManager(): ReportCustomerManager
    {
        return $this->get('report_customer.manager');
    }

    private function getCurrencyRepository(): CurrencyRepository
    {
        return $this->getDoctrine()->getRepository(Currency::class);
    }

    private function getCustomerRepository(): CustomerRepository
    {
        return $this->getDoctrine()->getRepository(Customer::class);
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->getDoctrine()->getRepository(Product::class);
    }
}
