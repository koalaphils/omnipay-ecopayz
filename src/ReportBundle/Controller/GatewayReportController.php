<?php

namespace ReportBundle\Controller;

use AppBundle\Controller\AbstractController;
use DbBundle\Entity\Currency;
use DbBundle\Entity\Gateway;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\GatewayRepository;
use DbBundle\Repository\CurrencyRepository;
use ReportBundle\Manager\ReportGatewayManager;
use GatewayBundle\Manager\GatewayManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GatewayReportController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_REPORT_GATEWAY_VIEW']);
        $this->getSession()->save();
        $currencies = $this->getCurrencyRepository()->getCurrencyList();

        return $this->render('ReportBundle:Report:gateways/gateways.html.twig', ['currencies' => $currencies]);
    }

    public function currencyReportAction(Request $request, $currency)
    {
        if ($request->get('export', false) && $this->has('profiler')) {
            $this->get('profiler')->disable();
        }
        $this->getSession()->save();
        $filters = $request->get('filters');
        $filters['currency'] = $currency;

        if (array_has($filters, 'from')) {
            $filters['from'] = \DateTime::createFromFormat('m/d/Y', $filters['from'])->format('Y-m-d');
        }
        if (array_has($filters, 'to')) {
            $filters['to'] = \DateTime::createFromFormat('m/d/Y', $filters['to'])->format('Y-m-d');
        }

        $report = $this->getManager()->getReportPaymentGatewayList($filters);

        return $this->jsonResponse($report);
    }

    public function gatewayTransactionsReportAction(Request $request, $id)
    {
        $filters = $request->get('filters');
        $gateway = $this->getGatewayManager()->findOneById($id);
        $filters['gateways'] = [$id];
        $gatewayReport = $this->getManager()->getReportPaymentGatewayList($filters)[0];
        $transactionCompletedStatusId = Transaction::TRANSACTION_STATUS_END;

        return $this->render(
            'ReportBundle:Report:gateways/transactions.html.twig',
            [
                'gateway' => $gateway,
                'filters' => $filters,
                'report' => $gatewayReport,
                'transactionCompletedStatusId' => $transactionCompletedStatusId,
            ]
        );
    }

    public function exportGatewaysAction(Request $request,string $currencyCode)
    {
        $currency = $this->getManager()->findCurrencyByCode($currencyCode);
        $filters = $this->buildFiltersForGatewayReportExport($currency, $request);

        $response = new StreamedResponse(function () use ($filters) {
            $this->getManager()->printGatewayCsvReport($filters);
        });

        $filename = $this->getManager()->getGatewayReportFileName($currency, $filters);

        $this->setResponseTypeAsCSVFile($response, $filename);

        return $response;

    }

    private function buildFiltersForGatewayReportExport(Currency $currency, Request $request): Array
    {
        $filters = $request->get('filters', []);
        $filters['currency'] = $currency->getCode();

        if (array_has($filters, 'from')) {
            $filters['from'] = \DateTime::createFromFormat('m/d/Y', $filters['from'])->format('Y-m-d');
        }
        if (array_has($filters, 'to')) {
            $filters['to'] = \DateTime::createFromFormat('m/d/Y', $filters['to'])->format('Y-m-d');
        }

        if (!isset($filters['search'])) {
            $filters['search'] = '';
        }

        return $filters;
    }

    public function exportGatewayTransactionsAction(Request $request, $id)
    {
        $response = new StreamedResponse(function () use ($request) {
            $this->get('transaction.manager')->printGatewayTransactionsCsvReport($request);
        });

        $filters = $request->get('filters', []);
        $filename = $this->getManager()->getGatewayTransactionReportFileName($id, $filters);
        $this->setResponseTypeAsCSVFile($response, $filename);

        return $response;
    }

    protected function getManager(): ReportGatewayManager
    {
        return $this->get('report_gataeway.manager');
    }

    private function getCurrencyRepository(): CurrencyRepository
    {
        return $this->getDoctrine()->getRepository(Currency::class);
    }

    private function getGatewayManager(): GatewayManager
    {
        return $this->get('gateway.manager');
    }
}
