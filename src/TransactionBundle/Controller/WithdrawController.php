<?php

declare(strict_types = 1);

namespace TransactionBundle\Controller;

use AppBundle\Controller\AbstractController;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\TransactionRepository;
use PinnacleBundle\Service\PinnacleService;
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
        int $id
    ): Response {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_UPDATE']);
        $this->saveSession();
        $this->setMenu('transaction.list');
        $pinnacleProduct = $pinnacleService->getPinnacleProduct();

        $transaction = $transactionRepository->findByIdAndType($id, Transaction::TRANSACTION_TYPE_WITHDRAW);
        $pinnacleTransacted = 0;
        $pinnacleTransactionDates = [];
        foreach ($transaction->getSubTransactions() as $subTransaction) {
            if ($subTransaction->getDetail('pinnacle.transacted')) {
                $pinnacleTransacted++;
            }
            if ($subTransaction->getCustomerProduct()->getProduct()->getCode() === $pinnacleProduct->getCode()) {
                $playerInfo = $pinnacleService->getPlayerComponent()->getPlayer($subTransaction->getCustomerProduct()->getUserName());
                $subTransaction->getCustomerProduct()->setBalance($playerInfo->availableBalance());
                $pinnacleTransactionDate = $subTransaction->getDetail('pinnacle.transaction_dates', []);
                foreach ($pinnacleTransactionDate as $type => $info) {
                    if ($info['status'] === 'voided') {
                        $statusText = "Voided";
                    } else {
                        $statusText = $transactionManager->getStatus($info['status'])['label'];
                    }

                    $pinnacleTransactionDates[$type] = $info['date'];
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
