<?php

namespace ReportBundle\Controller;

use AppBundle\Controller\AbstractController;
use DbBundle\Entity\Currency;
use DbBundle\Entity\Product;
use DbBundle\Repository\CurrencyRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductReportController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_REPORT_PRODUCT_VIEW']);
        $this->getSession()->save();
        $currencies = $this->getCurrencyRepository()->getCurrencyList();

        return $this->render('ReportBundle:Report:products/products.html.twig', ['currencies' => $currencies]);
    }

    public function currencyReportAction(Request $request, $currency)
    {
        if ($request->get('export', false) && $this->has('profiler')) {
            $this->get('profiler')->disable();
        }

        set_time_limit(0);
        $this->getSession()->save();
        $filters = $request->get('filters');
        $filters['currency'] = $currency;

        if (array_has($filters, 'from')) {
            $filters['from'] = \DateTime::createFromFormat('m/d/Y', $filters['from'])->format('Y-m-d');
        }
        if (array_has($filters, 'to')) {
            $filters['to'] = \DateTime::createFromFormat('m/d/Y', $filters['to'])->format('Y-m-d');
        }

        $reports = $this->getManager()->getReportProductList($filters);

        $reports = array_map(function ($report) use ($request, $currency) {
            $report['link'] = $this->getRouter()->generate(
                'report.product',
                ['currencyCode' => $currency, 'productId' => $report['product_id'], 'filters' => $request->get('filters')]
            );

            return $report;
        }, $reports);

        return $this->jsonResponse($reports);
    }

    // Product Report (per Currency) Page
    public function productReportPageAction(Request $request, $currencyCode, $productId)
    {
        $this->getSession()->save();
        $filtersOriginal = $request->get('filters');
        $filters = $filtersOriginal;
        $filters['products'] = [$productId];
        $filters['currency'] = $currencyCode;
        if (array_has($filters, 'from')) {
            $filters['from'] = \DateTime::createFromFormat('m/d/Y', $filters['from'])->format('Y-m-d');
        }
        if (array_has($filters, 'to')) {
            $filters['to'] = \DateTime::createFromFormat('m/d/Y', $filters['to'])->format('Y-m-d');
        }
        $product = $this->getProductRepository()->find($productId);
        $currency = $this->getCurrencyRepository()->findOneBy(['code' => $currencyCode]);
        $report = $this->getManager()->getReportProductList($filters)[0];

        return $this->render('ReportBundle:Report:products/customer-products.html.twig', [
            'currency' => $currency,
            'product' => $product,
            'filters' => $filtersOriginal,
            'report' => $report,
        ]);
    }

    public function customerProductDWlReportAction(Request $request, $currencyCode, $productId, $customerProductId)
    {
        $this->getSession()->save();
        $filters = $request->get('filters');

        $report = $this->getManager()->getCustomerProductDWL($customerProductId, $filters, $request->get('limit'), $request->get('page'));

        return $this->jsonResponse($report);
    }

    public function exportProductsAction(Request $request, $currency)
    {
        set_time_limit(0);
        $this->getSession()->save();
        $filters = $request->get('filters');

        $reportStartAt = \DateTime::createFromFormat('m/d/Y', ($filters['from'] ?? date('Y-m-d')));
        $reportEndAt = \DateTime::createFromFormat('m/d/Y', ($filters['to'] ?? date('Y-m-d')));
        $productSearchString = $filters['search'] ??  null;

        $currencyEntity = $this->getManager()->getCurrencyByCode($currency);
        $response = new StreamedResponse(function () use ($currencyEntity, $reportStartAt, $reportEndAt, $productSearchString) {
            $this->getManager()->printProductsCsvReport($currencyEntity, $reportStartAt, $reportEndAt, $productSearchString);
        });

        $filename = 'Products_'. $currency .'_'. $reportStartAt->format('Ymd') . '_'. $reportEndAt->format('Ymd') .'.csv';
        $this->setResponseTypeAsCSVFile($response, $filename);

        return $response;
    }

    protected function getManager(): \ReportBundle\Manager\ReportProductManager
    {
        return $this->get('report_product.manager');
    }

    private function getCurrencyRepository(): CurrencyRepository
    {
        return $this->getDoctrine()->getRepository(Currency::class);
    }

    private function getProductRepository(): \DbBundle\Repository\ProductRepository
    {
        return $this->getDoctrine()->getRepository(Product::class);
    }
}
