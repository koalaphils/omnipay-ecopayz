<?php

namespace TransactionBundle\Controller;

use AppBundle\Controller\AbstractController;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends AbstractController
{
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_VIEW']);
        $statuses = $this->getSettingManager()->getSetting('transaction.status');
        $statuses = $this->getManager()->addVoidedStatus($statuses);
        $this->getRepository('DbBundle:Setting')->updateSetting($key = 'transaction', $code = 'counter', $newCounter = 0);
        $filter = [];

        if (trim($request->get('filter', '')) !== '') {
            $filterName = $request->get('filter');
            $filter = $this->getSettingManager()->getSetting('transaction.list.filters.' . $filterName, []);
        }

        return $this->render('TransactionBundle:Default:index.html.twig', [
            'statuses' => $statuses,
            'filter' => $filter,
        ]);
    }

    public function searchAction(Request $request)
    {
        if ($request->get('export', false) && $this->has('profiler')) {
            $this->get('profiler')->disable();
        }

        $this->getSession()->save();
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_VIEW']);
        $results = $this->getManager()->findTransactions($request);
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

    public function downloadAction(Request $request)
    {
        $response = new StreamedResponse(function () use ($request) {
            $this->getManager()->printCsvReport($request);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="TransactionList_'. date('Ymd') .'.csv"');

        return $response;
    }


    public function createPageAction(Request $request, $type)
    {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_CREATE']);
        $transaction = new Transaction();
        $transaction->setNumber(date('Ymd-His-') . $this->getManager()->getType($type));
        $transaction->setType($this->getManager()->getType($type));
        $transaction->setDate(new \DateTime());

        $validationGroups = ['default'];
        if ($transaction->isDeposit() || $transaction->isWithdrawal()) {
            $validationGroups[] = 'hasFees';
            $validationGroups[] = 'withGateway';
            $validationGroups[] = 'withPaymentOption';
        }

        if ($transaction->isBonus()) {
            $validationGroups[] = 'withGateway';
        }

        $form = $this->getManager()->createForm($transaction, false, [
            'validation_groups' => $validationGroups,
        ]);

        if (!is_null($request->get('toCustomer', null))) {
            $toCustomer = $this->getRepository('DbBundle:Customer')->findById($request->get('toCustomer'), \Doctrine\ORM\Query::HYDRATE_ARRAY);
        } else {
            $toCustomer = null;
        }

        return $this->render("TransactionBundle:Transaction/Type:$type.html.twig", [
            'form' => $form->createView(),
            'type' => $type,
            'transaction' => $transaction,
            'toCustomer' => $toCustomer,
        ]);
    }

    public function updatePageAction(Request $request, $type, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_UPDATE']);
        $this->getSession()->save();
        $this->getMenuManager()->setActive('transaction.list');

        $transaction = $this->getRepository('DbBundle:Transaction')->findByIdAndType($id, $this->getManager()->getType($type));
        $dwl = null;

        // zimi-comment
        // if ($transaction->isDwl()) {
        //     $dwl = $this->getDWLRepository()->find($transaction->getDwlId());
        // }

        $form = $this->getManager()->createForm($transaction, false);

        if (!is_null($transaction->getDetail('toCustomer', null))) {
            $toCustomer = $this->getRepository('DbBundle:Customer')->findById($transaction->getDetail('toCustomer'), \Doctrine\ORM\Query::HYDRATE_OBJECT);
        } else {
            $toCustomer = null;
        }

        return $this->render("TransactionBundle:Transaction/Type:$type.html.twig", [
            'form' => $form->createView(),
            'type' => $type,
            'gateway' => $transaction->getGateway(),
            'transaction' => $transaction,
            'toCustomer' => $toCustomer,
            'dwl' => $dwl,
        ]);
    }

    public function saveAction(Request $request, $type, $id = 'new')
    {
        
        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_CREATE']);
            return $this->createAction($request, $type);
        }

        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_UPDATE']);

        return $this->updateAction($request, $type, $id);
    }

    public function getGatewayByTransactionAction(Request $request, $type)
    {
        if ($request->get('tid') !== '') {
            $transaction = $this->getRepository('DbBundle:Transaction')->findByIdAndType($request->get('tid'), $this->getManager()->getType($type));
        } else {
            $transaction = new Transaction();
            $transaction->setType($this->getManager()->getType($type));
        }

        $form = $this->getManager()->createForm($transaction, true, ['validation_groups' => ['noValidate']]);
        $form->handleRequest($request);

        $gateways = $this->getManager()->getGatewaysByTransaction($transaction);

        return $this->response($request, $gateways, ['groups' => ['Default', 'details', 'balance', 'currency']]);
    }

    public function countTransactionByStatusAction(Request $request)
    {
        $this->getSession()->save();
        $statuses = $this->getManager()->getCountPerStatus();

        return $this->response($request, $statuses, []);
    }

    public function voidTransactionAction(Request $request, $type, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_UPDATE']);
        $transactionRequest = $request->request->get('Transaction');
        $reasonForVoiding = array_has($transactionRequest, 'reasonToVoidOrDecline') ? strip_tags($transactionRequest['reasonToVoidOrDecline']) : '';
        $transaction = $this->getRepository('DbBundle:Transaction')->findByIdAndType($id, $this->getManager()->getType($type));
        if (!$transaction || empty($reasonForVoiding)) {
            if (empty($reasonForVoiding)) {
                return new JsonResponse([
                    '__notifications' => [
                        'type'      => 'error',
                        'title'     => 'Validation Failed',
                        'message_box'   => 'Reason is required.',
                        'message_notification'   => 'Some fields are invalid.',
                    ],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            throw new \Doctrine\ORM\NoResultException;
        } elseif (!$transaction->isVoided() && !$transaction->isDwl()) {
            $transaction->setReasonToVoidOrDecline($reasonForVoiding);
            $this->getManager()->processTransaction($transaction, 'void');
            $message = [
                'type'      => 'success',
                'title'     => 'Void',
                'message'   => 'Transaction number ('. $transaction->getNumber() . ') has been voided',
            ];
            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $message);

                return $this->redirect($request->headers->get('referer'), Response::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $message
                ], Response::HTTP_OK);
            }
        } else {
            throw new \Exception('Transaction number (' . $transaction->getNumber() . ') is already voided', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Get Transaction Manager.
     *
     * @return \TransactionBundle\Manager\TransactionManager
     */
    protected function getManager()
    {
        return $this->get('transaction.manager');
    }

    private function createAction(Request $request, $type)
    {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_CREATE']);
        $transaction = new Transaction();
        $transaction->setType($this->getManager()->getType($type));
        $validationGroups = ['default'];
        if ($transaction->isDeposit() || $transaction->isWithdrawal()) {
            $validationGroups[] = 'hasFees';
            $validationGroups[] = 'withGateway';
            $validationGroups[] = 'withPaymentOption';
        }
        if ($transaction->isBonus()) {
            $validationGroups[] = 'withGateway';
        }

        $form = $this->getManager()->createForm($transaction, true, [
            'validation_groups' => $validationGroups,
        ]);
        $response = ['success' => true];
        try {
            $transaction = $this->getManager()->handleFormTransaction($form, $request);
            $response['data'] = $transaction;
        } catch (\AppBundle\Exceptions\FormValidationException $e) {
            $response['success'] = false;
            $response['errors'] = $e->getErrors();
        }

        return $this->response($request, $response, ['groups' => ['Default', '_link']]);
    }

    private function isRequestToVoidOrDecline(Transaction $transaction, Request $request) : bool
    {
        $transactionRequest = $request->request->all('Transaction');
        $buttonName = key($transactionRequest['Transaction']['actions']);
        $buttonName = str_replace('btn_',''  , $buttonName);
        if ($buttonName == 'decline' || $buttonName == 'void') {
            return true;
        }
        return false;
    }

    private function updateAction(Request $request, $type, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_UPDATE']);
        $transaction = $this->getRepository('DbBundle:Transaction')->findByIdAndType($id, $this->getManager()->getType($type));
        $isForVoidingOrDecline = $this->isRequestToVoidOrDecline($transaction, $request);
        $form = $this->getManager()->createForm($transaction, true, [
            'isForVoidingOrDecline' => $isForVoidingOrDecline,
        ]);

        $response = ['success' => true];
        try {
            // zimi
            $transaction = $this->getManager()->handleFormTransaction($form, $request);

            $response['data'] = $transaction;
        } catch (\AppBundle\Exceptions\FormValidationException $e) {
            $response['success'] = false;
            $response['errors'] = $e->getErrors();
        }

        return $this->response($request, $response, ['groups' => ['Default', '_link']]);
    }

    private function getDWLRepository(): \DbBundle\Repository\DWLRepository
    {
        return $this->getRepository(\DbBundle\Entity\DWL::class);
    }
}