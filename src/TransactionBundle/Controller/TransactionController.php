<?php

declare(strict_types = 1);

namespace TransactionBundle\Controller;

use AppBundle\Controller\AbstractController;
use AppBundle\Manager\SettingManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TransactionBundle\Manager\TransactionManager;

class TransactionController extends AbstractController
{
    public function indexAction(Request $request, TransactionManager $transactionManager, SettingManager $settingManager)
    {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_VIEW']);
        $statuses = $transactionManager->getTransactionStatus();
        $settingManager->updateSetting('counter.transaction', 0);
        $filter = [];
        $nonPendingStatuses = [];

        if (trim($request->get('filter', '')) !== '') {
            $filterName = $request->get('filter');
            $filter = $settingManager->getSetting('transaction.list.filters.' . $filterName, []);
        } else {
            $nonPendingStatuses = $transactionManager->getNonPendingTransactionStatus($statuses);
        }

        return $this->render('TransactionBundle:Default:index.html.twig', [
            'statuses' => $statuses,
            'nonPendingStatuses' => $nonPendingStatuses,
            'filter' => $filter,
        ]);
    }

    public function searchAction(Request $request, TransactionManager $transactionManager)
    {
        if ($request->get('export', false)) {
            $this->disableProfiler();
        }

        $this->saveSession();
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_VIEW']);
        $results = $transactionManager->findTransactions($request);
        $context = $this->createSerializationContext([
            'Search',
            '_link',
            'Default',
            'customer',
            'customer' => ['name', 'user'],
            'createdBy',
            'subtransactions_group',
            'dwl',
        ]);

        return $this->jsonResponse($results, Response::HTTP_OK, [], $context);
    }

    public function updatePageAction(string $type, int $id): Response
    {
        if ($type === 'deposit') {
            $response = $this->forward('TransactionBundle:Deposit:updatePage', ['id' => $id, 'type' => $type]);
        } elseif ($type === 'withdraw') {
            $response = $this->forward('TransactionBundle:Withdraw:updatePage', ['id' => $id, 'type' => $type]);
        } else {
            throw $this->createNotFoundException();
        }

        return $response;
    }

    public function saveAction(string $type, int $id): Response
    {
        if ($type === 'deposit') {
            $response = $this->forward('TransactionBundle:Deposit:save', ['id' => $id, 'type' => $type]);
        } else {
            throw $this->createNotFoundException();
        }

        return $response;
    }

    public function countTransactionByStatusAction(Request $request, TransactionManager $transactionManager)
    {
        $this->saveSession();

        $statuses = $transactionManager->getCountPerStatus();

        return $this->response($request, $statuses, []);
    }
}