<?php

declare(strict_types = 1);

namespace TransactionBundle\Controller;

use ApiBundle\Service\JWTGeneratorService;
use AppBundle\Controller\AbstractController;
use DbBundle\Entity\Product;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\TransactionRepository;
use PinnacleBundle\Service\PinnacleService;
use ProductIntegrationBundle\Integration\ProductIntegrationInterface;
use ProductIntegrationBundle\ProductIntegrationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TransactionBundle\Manager\TransactionManager;

class WithdrawController extends AbstractController
{
    public function updatePageAction(
        Request $request,
        TransactionRepository $transactionRepository,
        TransactionManager $transactionManager,
        PinnacleService $pinnacleService,
        JWTGeneratorService $jwtService,
        ProductIntegrationFactory $productIntegrationFactory,
        int $id
    ): Response {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_UPDATE']);
        $this->saveSession();
        $this->setMenu('transaction.list');

        $pinnacleProduct = $pinnacleService->getPinnacleProduct();
	    $jwt = $jwtService->generate([]);
	    $integratedProducts = [Product::SPORTS_CODE, Product::EVOLUTION_PRODUCT_CODE, Product::PIWIXCHANGE_CODE];
        $transaction = $transactionRepository->findByIdAndType($id, Transaction::TRANSACTION_TYPE_WITHDRAW);
        $pinnacleTransacted = 0;
        $pinnacleTransactionDates = [];

        foreach ($transaction->getSubTransactions() as $subTransaction) {
	        $productCode = $subTransaction->getCustomerProduct()->getProduct()->getCode();
	        if (in_array($productCode, $integratedProducts))
	        {
		        $playerAvailableBalance = 0;
		        try {
			        /** @var ProductIntegrationInterface $integration */
			        $integration = $productIntegrationFactory->getIntegration($productCode);
			        $playerAvailableBalance = $integration->getBalance($jwt, $subTransaction->getCustomerProduct()->getUserName());
		        } catch (\Exception $ex) {
			        $subTransaction->setFailedProcessingWithIntegration(true);
		        }
		        $subTransaction->getCustomerProduct()->setBalance($playerAvailableBalance);

		        // PIN Product - set Pinnacle Transaction Date
		        if ($productCode === $pinnacleProduct->getCode()) {
			        if ($subTransaction->getDetail('pinnacle.transacted')) {
				        $pinnacleTransacted++;
			        }
			        $pinnacleTransactionDate = $subTransaction->getDetail('pinnacle.transaction_dates', []);
			        foreach ($pinnacleTransactionDate as $type => $info) {
				        $pinnacleTransactionDates[$type] = $info['date'];
			        }
		        }
	        }
        }
        $form = $transactionManager->createForm($transaction, false);

        $transactionDates = [];
        foreach ($transaction->getDetail('transaction_dates', []) as $statusId => $transactionDate) {
            if ($statusId === 'void') {
                $transactionDates['Voided'] = $transactionDate;
            } else {
                $transactionDates[$transactionManager->getStatus($statusId)['label']] = $transactionDate;
            }
        }
        asort($transactionDates);
        asort($pinnacleTransactionDates);

	    $context = [
		    'form' => $form->createView(),
		    'type' => 'withdraw',
		    'gateway' => $transaction->getGateway(),
		    'transaction' => $transaction,
		    'pinnacleTransacted' => $pinnacleTransacted === count($transaction->getSubTransactions()),
		    'transactionDates' => $transactionDates,
		    'pinnacleTransactionDates' => $pinnacleTransactionDates,
	    ];

	    if ($transaction->isPaymentBitcoin() && !empty($btcTransactDetails = $transactionManager->getBitcoinTransactionDetails($transaction)))
	    {
		    $context['bitcoinTransactionDetails'] = $btcTransactDetails;
	    }

        return $this->render("TransactionBundle:Transaction/Type:withdraw.html.twig", $context);
    }

    public function saveAction(Request $request, TransactionRepository $transactionRepository, TransactionManager $transactionManager, int $id): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_UPDATE']);
        $this->saveSession();

        $transaction = $transactionRepository->findByIdAndType($id, Transaction::TRANSACTION_TYPE_WITHDRAW);
        $form = $transactionManager->createForm($transaction, true);

        return new Response();
    }
}
