<?php

namespace TransactionBundle\Controller;

use AppBundle\Controller\AbstractController;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use Doctrine\ORM\PersistentCollection;
use PinnacleBundle\Component\Exceptions\PinnacleError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use MemberBundle\Manager\MemberManager;
use Doctrine\DBAL\LockMode;
use ApiBundle\Controller\TransactionController as ApiTransaction;

class TransactionOldController extends AbstractController
{
    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_VIEW']);
        $statuses = $this->getManager()->getTransactionStatus();
        $this->getRepository('DbBundle:Setting')->updateSetting($key = 'transaction', $code = 'counter', $newCounter = 0);
        $filter = [];
        $nonPendingStatuses = [];

        if (trim($request->get('filter', '')) !== '') {
            $filterName = $request->get('filter');
            $filter = $this->getSettingManager()->getSetting('transaction.list.filters.' . $filterName, []);
        } else {
            $nonPendingStatuses = $this->getManager()->getNonPendingTransactionStatus($statuses);
        }

        return $this->render('TransactionBundle:Default:indfex.html.twig', [
            'statuses' => $statuses,
            'nonPendingStatuses' => $nonPendingStatuses,
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
        $transaction->setNumber($this->getManager()->generateTransactionNumber($type));
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
            'pinnacleTransacted' => false,
            'transactionDates' => [],
            'pinnacleTransactionDates' => [],
        ]);
    }

    public function updatePageAction(Request $request, $type, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_UPDATE']);
        $this->getSession()->save();
        $this->getMenuManager()->setActive('transaction.list');

        $transaction = $this->getRepository('DbBundle:Transaction')->findByIdAndType($id, $this->getManager()->getType($type));
        // zimi
        $customer = $transaction->getCustomer();
        $username = $customer->getUsername();
        $user = $customer->getUser();

        // zimi# 1: email, 2: phone
        $userType = $user->getType();
        if ($userType == 1) {
            $username = $user->getEmail();
        } else {
            $username = $user->getPhoneNumber();
        }

        $dwl = null;
        $memberRunningCommission = null;
        $commissionPeriod = null;

        if ($transaction->isDwl()) {
            $dwl = $this->getDWLRepository()->find($transaction->getDwlId());
        } elseif ($transaction->isCommission()) {
            $memberRunningCommission = $this->getRepository('DbBundle:MemberRunningCommission')->findOneByCommissionTransaction($transaction->getId());
            $commissionPeriod = $this->getRepository('DbBundle:CommissionPeriod')->findOneById($memberRunningCommission->getCommissionPeriodId());
        }

        $form = $this->getManager()->createForm($transaction, false);

        if (!is_null($transaction->getDetail('toCustomer', null))) {
            $toCustomer = $this->getRepository('DbBundle:Customer')->findById($transaction->getDetail('toCustomer'), \Doctrine\ORM\Query::HYDRATE_OBJECT);
        } else {
            $toCustomer = null;
        }

        // zimi
        $template = $this->get('twig');
        $template->addGlobal('trans', $transaction);
        $template->addGlobal('customer', $customer);

        $userCode = $customer->getPinUserCode();
        $apiTran = new ApiTransaction();
        $balance = $apiTran->getAvailableBalance($userCode);

        if ($transaction->isDeposit() == true) {
            $afterBalance = (float)$balance + (float)$transaction->getAmount();
        }

        if ($transaction->isWithdrawal() == true) {
            $afterBalance = (float)$balance - (float)$transaction->getAmount();
        }

        // check $balance failure
        if ($balance == -1) {
            $balance = '<i class="fa fa-exclamation"></i>';
            $afterBalance = '<i class="fa fa-exclamation"></i>';
        }

        $template->addGlobal('currentBalance', $balance);
        $template->addGlobal('afterBalance', $afterBalance);

        return $this->render("TransactionBundle:Transaction/Type:$type.html.twig", [
            'form' => $form->createView(),
            'type' => $type,
            'gateway' => $transaction->getGateway(),
            'transaction' => $transaction,
            'toCustomer' => $toCustomer,
            'dwl' => $dwl,
            'memberRunningCommission' => $memberRunningCommission,
            'commissionPeriod' => $commissionPeriod,
            'memberUsername' => $username,
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

        try {
            $this->getManager()->beginTransaction();
            $transactionRequest = $request->request->get('Transaction');
            $reasonForVoiding = array_has($transactionRequest, 'reasonToVoidOrDecline') ? strip_tags($transactionRequest['reasonToVoidOrDecline']) : '';
            $transaction = $this->getRepository('DbBundle:Transaction')->findByIdAndType($id, $this->getManager()->getType($type), \Doctrine\ORM\Query::HYDRATE_OBJECT, LockMode::PESSIMISTIC_WRITE);
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
                $this->getManager()->commit();
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
        } catch (PessimisticLockException $e) {
            $this->getManager()->rollBack();
            $notifications = [
                'type' => 'error',
                'title' => $this->getTranslator()->trans('notifications.notUpdatedForm.title', [], 'TransactionBundle'),
                'message' => $this->getTranslator()->trans('notifications.notUpdatedForm.message', [], 'TransactionBundle'),
            ];
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['__notifications' => [$notifications]], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } catch (Exception $e) {
            $this->getManager()->rollBack();

            throw $e;
        }
    }

    public function viewDwlTransactionAction(int $id): Response
    {
        $this->getSession()->save();
        try {
            $transaction = $this->getManager()->getTransactionById($id);
            if (!$transaction->isDwl()) {
                throw $this->createNotFoundException('Not found');
            }

            $dwl = $this->getDWLRepository()->find($transaction->getDwlId());

            return $this->render('TransactionBundle:Transaction/view:dwl.html.twig', ['transaction' => $transaction, 'dwl' => $dwl]);
        } catch (\Doctrine\ORM\NoResultException $e) {
            throw $this->createNotFoundException('Not found', $e);
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

    protected function getMemberManager(): MemberManager
    {
        return $this->get('member.manager');
    }

    private function createAction(Request $request, $type)
    {
        try {
            $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_CREATE']);
            $this->getManager()->beginTransaction();
            $isPaymentOptionIdBitcoin = false;
            $validationGroups = ['default'];
            $transaction = new Transaction();
            $transaction->setType($this->getManager()->getType($type));
            $transactionRequest = $request->request->get('Transaction');
            if ($transaction->hasAdjustment() && isset($transactionRequest['adjustment']) && !empty($transactionRequest['adjustment'])) {
                $adjustmentType = $this->getManager()->getAdjustmentType($transactionRequest['adjustment']);
                $transaction->setType($adjustmentType);
            }
            if ($transaction->isDeposit() || $transaction->isWithdrawal()) {
                $validationGroups[] = 'hasFees';
                $validationGroups[] = 'withGateway';
                $validationGroups[] = 'withPaymentOption';
            }
            if ($transaction->isBonus()) {
                $validationGroups[] = 'withGateway';
            }
            if ($transaction->hasAdjustment()) {
                $validationGroups[] = 'withAdjustment';
            }

            if (array_has($transactionRequest, 'paymentOption')) {
                $isPaymentOptionIdBitcoin = $this->getMemberManager()->isPaymentOptionIdBitcoin($transactionRequest['paymentOption']);
            }

            if ($transaction->isNew() && $transaction->isDeposit() && $isPaymentOptionIdBitcoin) {
                $validationGroups[] = 'withBitcoin';
                $transaction->setBitcoinConfirmationAsConfirmed();
            }

            $form = $this->getManager()->createForm($transaction, true, [
                'validation_groups' => $validationGroups,
            ]);
            $response = ['success' => true];
            try {
                if ($transaction->isDeposit() && $isPaymentOptionIdBitcoin) {
                    $this->getMemberManager()->updateMemberPaymentOptionBitcoinAddress($transactionRequest);
                }
                $transaction = $this->getManager()->handleFormTransaction($form, $request);
                $response['data'] = $transaction;
            } catch (\AppBundle\Exceptions\FormValidationException $e) {
                $response['success'] = false;
                $response['errors'] = $e->getErrors();
            }
            $this->getManager()->commit();
        } catch (PessimisticLockException $e) {
            $this->getManager()->rollBack();
            $notifications = [
                'type' => 'error',
                'title' => $this->getTranslator()->trans('notifications.notUpdatedForm.title', [], 'TransactionBundle'),
                'message' => $this->getTranslator()->trans('notifications.notUpdatedForm.message', [], 'TransactionBundle'),
            ];
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['__notifications' => [$notifications]], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } catch (Exception $e) {
            $this->getManager()->rollBack();

            throw $e;
        }

        return $this->response($request, $response, ['groups' => ['Default', '_link']]);
    }

    private function isRequestToVoidOrDecline(Transaction $transaction, Request $request) : bool
    {
        $transactionRequest = $request->request->all('Transaction');
        $buttonName = key($transactionRequest['Transaction']['actions']);
        $buttonName = str_replace('btn_',''  , $buttonName);
        if ($buttonName == 'decline' || $buttonName == 'void' || $buttonName == 'confirm') {
            return true;
        }
        return false;
    }

    private function updateAction(Request $request, $type, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_TRANSACTION_UPDATE']);

        try {
            $this->getManager()->beginTransaction();
            /* @var $transaction Transaction */
            $transaction = $this->getRepository('DbBundle:Transaction')->findByIdAndType($id, $this->getManager()->getType($type), \Doctrine\ORM\Query::HYDRATE_OBJECT, LockMode::PESSIMISTIC_WRITE);
            $isForVoidingOrDecline = $this->isRequestToVoidOrDecline($transaction, $request);

            if (array_has($request->get('Transaction'), 'actions.btn_decline')) {
                $validationGroups = ['default'];
            } else {
                $validationGroups = ['default', $type];
            }


            if ($type === 'withdraw' && !array_has($request->get('Transaction'), 'actions.btn_decline')) {
                $validationGroups[] = 'withGateway';
            }

            $formAction = $this->getManager()->getAction($transaction->getStatus(), $request->get('btn_value'), $transaction->getTypeText());

            if ($formAction !== null) {
                if ($transaction->isDeposit() && $this->getSetting('pinnacle.transaction.deposit.status') == $formAction['status']) {
                    $validationGroups[] = 'withGateway';
                }

                if ($transaction->isWithdrawal() && $this->getSetting('pinnacle.transaction.withdraw.status') == $formAction['status']) {
                    $validationGroups[] = 'withGateway';
                }
            }

            $form = $this->getManager()->createForm($transaction, true, [
                'isForVoidingOrDecline' => $isForVoidingOrDecline,
                'validation_groups' => $validationGroups,
            ]);

            $response = ['success' => true];
            try {
                $old_status = $transaction->getStatus();
                $transaction = $this->getManager()->handleFormTransaction($form, $request);
                $this->getManager()->insertTransactionLog($transaction, $old_status, $this->getUser()->getId());
                $this->getManager()->insertNotificationByTransaction($transaction);

                $response['data'] = $transaction;
                $this->getManager()->commit();
            } catch (\AppBundle\Exceptions\FormValidationException $e) {
                $response['success'] = false;
                $response['errors'] = $e->getErrors();
            } catch (\ApiBundle\ProductIntegration\IntegrationNotAvailableException  $e) {
                $response['success'] = false;
                $response['errorMessage'] = $e->getMessage();
            }   
        } catch (PessimisticLockException $e) {
            $this->getManager()->rollBack();
            $notifications = [
                'type' => 'error',
                'title' => $this->getTranslator()->trans('notifications.notUpdatedForm.title', [], 'TransactionBundle'),
                'message' => $this->getTranslator()->trans('notifications.notUpdatedForm.message', [], 'TransactionBundle'),
            ];
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['__notifications' => [$notifications]], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } catch (Exception $e) {
            $this->getManager()->rollBack();

            throw $e;
        }

        return $this->response($request, $response, ['groups' => ['Default', '_link']]);
    }

    private function getDWLRepository(): \DbBundle\Repository\DWLRepository
    {
        return $this->getRepository(\DbBundle\Entity\DWL::class);
    }
}
