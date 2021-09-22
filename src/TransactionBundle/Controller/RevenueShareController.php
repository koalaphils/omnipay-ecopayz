<?php

declare(strict_types = 1);

namespace TransactionBundle\Controller;

use AppBundle\Controller\AbstractController;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\TransactionRepository;
use DbBundle\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TransactionBundle\Manager\TransactionManager;

class RevenueShareController extends AbstractController
{
    public function updatePageAction(
        Request $request,
        TransactionRepository $transactionRepository,
        ProductRepository $productRepository,
        TransactionManager $transactionManager,
        int $id
    ): Response {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_UPDATE']);
        $this->saveSession();
        $this->setMenu('transaction.list');

        $piwiWallet = $productRepository->getPiwiWalletProduct();
        $transaction = $transactionRepository->findByIdAndType($id, Transaction::TRANSACTION_TYPE_REVENUE_SHARE);
        $form = $transactionManager->createForm($transaction, false);
        $commissionPeriod = $transactionManager->getCommissionPeriodForRevenueShareTransaction((int)$transaction->getId());


	    $context = [
		    'form' => $form->createView(),
		    'type' => 'revenue_share',
		    'gateway' => $transaction->getGateway(),
		    'transaction' => $transaction,
		    'commissionPeriod' => $commissionPeriod,
		    'pinnacleTransacted' => false,
	    ];

	    if ($transaction->isPaymentBitcoin() && !empty($btcTransactDetails = $transactionManager->getBitcoinTransactionDetails($transaction)))
	    {
		    $context['bitcoinTransactionDetails'] = $btcTransactDetails;
	    }

        return $this->render("TransactionBundle:Transaction/Type:revenue_share.html.twig", $context);
    }
}