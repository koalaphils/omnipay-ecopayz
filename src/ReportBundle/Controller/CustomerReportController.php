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
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Description of CustomerReportController
 *
 * @author Cydrick Nonog <cydrick.dev@gmail.com>
 */
class CustomerReportController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_REPORT_CUSTOMER_VIEW']);
        $this->getSession()->save();
        $currencies = $this->getCurrencyRepository()->getCurrencyList();

        return $this->render('ReportBundle:Report:customers/customers.html.twig', ['currencies' => $currencies]);
    }

    public function currencyReportAction(Request $request, $currency)
    {
        if ($request->get('export', false) && $this->has('profiler')) {
            $this->get('profiler')->disable();
        }
        set_time_limit(0);
        $this->getSession()->save();
        $filters = $request->get('filters');
        $currency = $this->getCurrencyRepository()->findOneBy(['code' => $currency]);
        $currencyEntity = $currency;
        $filters['currency'] = $currency->getId();

        if (array_has($filters, 'from')) {
            $filters['from'] = \DateTime::createFromFormat('m/d/Y', $filters['from'])->format('Y-m-d');
        }
        if (array_has($filters, 'to')) {
            $filters['to'] = \DateTime::createFromFormat('m/d/Y', $filters['to'])->format('Y-m-d');
        }
        $report = $this->getManager()->getReportCustomerList($filters, $request->get('limit', 20), $request->get('page', 1));
        $report['records'] = array_map(
            function ($customer) use ($filters, $request) {
                $customer['link'] = $this->getRouter()->generate(
                    'report.customer',
                    [
                        'currency' => $customer['dwl']['currency_code'],
                        'customerId' => $customer['dwl']['customer_id'],
                        'filters' => $request->get('filters', []),
                    ]
                );

                return $customer;
            },
            $report['records']
        );

        $report['totalSummary'] = []; //$this->getManager()->getReportCustomerTotal($currencyEntity, new \DateTimeImmutable($filters['from']), new \DateTimeImmutable($filters['to']), array_get($filters, 'customer_search', null));

        return $this->jsonResponse($report);
    }

    public function memberReportSummaryAction(Request $request, $currency)
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

    public function customerPageAction(Request $request, $currency, $customerId)
    {
        $this->getSession()->save();
        $currency = $this->getCurrencyRepository()->findOneBy(['code' => $currency]);
        $customer = $this->getCustomerRepository()->find($customerId);

        $filters = $request->get('filters', []);
        $reportFilters = $filters;
        if (array_has($reportFilters, 'from')) {
            $reportFilters['from'] = \DateTime::createFromFormat('m/d/Y', $reportFilters['from'])->format('Y-m-d');
        }
        if (array_has($filters, 'to')) {
            $reportFilters['to'] = \DateTime::createFromFormat('m/d/Y', $reportFilters['to'])->format('Y-m-d');
        }
        $reportFilters['customer'] = $customer->getId();

        $limit = 1;
        $page = 1;
        $customerReport = $this->getManager()->getReportCustomerList($reportFilters, $limit, $page)['records'][0];

        return $this->render('ReportBundle:Report:customers/customer.html.twig', [
            'currency' => $currency,
            'customer' => $customer,
            'filters' => $filters,
            'report' => $customerReport,
        ]);
    }


    public function customerProductsReportAction(Request $request)
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
        if (array_has($filters, 'products')) {
            $totalFilters['products'] = $filters['products'];
        }

        if (array_has($filters, 'search')) {
            $totalFilters['search'] = $filters['search'];
        }

        $report = $this->getManager()->getCustomerProductList($filters, $request->get('limit', 20), $request->get('page', 1));

        $reportSummary = $this->getManager()->getMemberProductsReportSummary($filters['products'][0], $filters['currency'],$reportStartDate , $reportEndDate, $filters['search']);

        $report['totalSummary']['turnover'] = $reportSummary['turnOverTotal'];
        $report['totalSummary']['gross_commission'] = $reportSummary['grossCommissionTotal'];
        $report['totalSummary']['win_loss'] = $reportSummary['winlossTotal'];
        $report['totalSummary']['commission'] = $reportSummary['commissionTotal'];
        $report['totalSummary']['availableBalaneAsOfReportEndDateTotal'] = $reportSummary['availableBalaneAsOfReportEndDateTotal'];
        $report['totalSummary']['currentBalanceTotal'] = $reportSummary['currentBalanceTotal'];



        return $this->jsonResponse($report);
    }

    public function exportCustomersAction(Request $request, $currency)
    {
        $filters = $request->get('filters', []);
        $currencyEntity = $this->getEntityManager()->getRepository(Currency::class)->findOneByCode($currency);
        $reportStartDate =  new \DateTimeImmutable($filters['from']);
        $reportEndDate = new \DateTimeImmutable($filters['to']);
        $customerNameQueryString = array_get($filters, 'customer_search', null);

        $response = new StreamedResponse(function () use (
            $currencyEntity,
            $reportStartDate,
            $reportEndDate,
            $customerNameQueryString
        ) {
            $this->getManager()->printCsvReport($currencyEntity, $reportStartDate, $reportEndDate, $customerNameQueryString);
        });

        $filename =  'CustomersReport_'. $currencyEntity->getCode() .'_'. $reportStartDate->format('Ymd') .'_'. $reportEndDate->format('Ymd') .'.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'. $filename .'"');

        return $response;
    }

    // csv export for Product Reports page
    public function exportCustomerProductsAction(Request $request)
    {
        $filters = $request->get('filters', []);
        $currencyEntity = $this->getEntityManager()->getRepository(Currency::class)->findOneByid($filters['currency']);
        $reportStartDate =  new \DateTimeImmutable($filters['from']);
        $reportEndDate = new \DateTimeImmutable($filters['to']);
        $memberProductUsernameQueryString = array_get($filters, 'search', null);

        $product = $this->getProductRepository()->find($filters['products'][0]);

        $response = new StreamedResponse(function () use ($product, $currencyEntity, $reportStartDate, $reportEndDate, $memberProductUsernameQueryString) {
            $this->getManager()->printMemberProductsCsvReport($product, $currencyEntity, $reportStartDate, $reportEndDate, $memberProductUsernameQueryString);
        });

        $filename =  $this->getMemberProductReportFilename($product, $currencyEntity, $reportStartDate, $reportEndDate);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'. $filename .'"');

        return $response;

    }

    private function getMemberProductReportFilename(Product $product, Currency $currency, \DateTimeInterface $startDate, \DateTimeInterface $endDate): String
    {
        return $product->getName() . '_' . $currency->getCode() . '_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.csv';
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
