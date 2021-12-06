<?php

declare(strict_types = 1);

namespace TransactionBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Controller\AbstractController;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\TransactionRepository;
use TransactionBundle\Manager\TransactionManager;

class TransferController extends  AbstractController
{
    public function updatePageAction(Request $request, 
        TransactionRepository $transactionRepository,
        TransactionManager $transactionManager,
        int $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_UPDATE']);
        $this->saveSession();
        $this->setMenu('transaction.list');

        $transaction = $transactionRepository->findByIdAndType($id, Transaction::TRANSACTION_TYPE_TRANSFER);
        $form = $transactionManager->createForm($transaction, false);

		$context = [
			'form' => $form->createView(),
			'type' => 'transfer',
			'transaction' => $transaction,
			'pinnacleTransacted' => '0',
		];

	    if ($transaction->isPaymentBitcoin() && !empty($btcTransactDetails = $transactionManager->getBitcoinTransactionDetails($transaction)))
	    {
		    $context['bitcoinTransactionDetails'] = $btcTransactDetails;
	    }

        return $this->render('TransactionBundle:Transaction/Type:transfer.html.twig', $context);
    }
}
