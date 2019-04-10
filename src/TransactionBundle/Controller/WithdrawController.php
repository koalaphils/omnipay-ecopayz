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
        foreach ($transaction->getSubTransactions() as $subTransaction) {
            if ($subTransaction->getCustomerProduct()->getProduct()->getCode() === $pinnacleProduct->getCode()) {
                $playerInfo = $pinnacleService->getPlayerComponent()->getPlayer($subTransaction->getCustomerProduct()->getUserName());
                $subTransaction->getCustomerProduct()->setBalance($playerInfo->availableBalance());
            }
        }
        $form = $transactionManager->createForm($transaction, false);

        return $this->render("TransactionBundle:Transaction/Type:withdraw.html.twig", [
            'form' => $form->createView(),
            'type' => 'withdraw',
            'gateway' => $transaction->getGateway(),
            'transaction' => $transaction,
        ]);
    }
}