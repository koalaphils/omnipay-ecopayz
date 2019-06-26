<?php

namespace PaymentOptionBundle\Controller;

use AppBundle\Controller\AbstractController;
use AppBundle\Exceptions\FormValidationException;
use DbBundle\Entity\Transaction;
use PaymentOptionBundle\Form\PaymentOptionType;
use PaymentBundle\Form\BitcoinRateSettingsDTOType;
use PaymentBundle\Form\BitcoinWithdrawalRateSettingsDTOType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use PaymentBundle\Manager\BitcoinManager;
use PaymentBundle\Form\BitcoinSettingType;
use PaymentBundle\Model\Bitcoin\SettingModel;
use PaymentBundle\Component\Blockchain\Rate;
use Doctrine\ORM\NoResultException;

class PaymentOptionController extends AbstractController
{
    public function listPageAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_VIEW']);

        return $this->render('PaymentOptionBundle:PaymentOption:list.html.twig');
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_VIEW']);

        $filters = $request->request->all();
        $filters = array_merge($filters, $request->query->all());
        $data = $this->getManager()->filter($filters);

        return $this->response($request, $data, ['groups' => ['Default', '_link']]);
    }

    public function createPageAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_CREATE']);

        $form = $this->createForm(PaymentOptionType::class, null, [
            'action' => $this->generateUrl('paymentoption.save', ['id' => 'new']),
            'method' => 'POST',
        ]);

        return $this->render('PaymentOptionBundle:PaymentOption:create.html.twig', ['form' => $form->createView()]);
    }

    public function updatePageAction(Request $request, $id, $activeTab)
    {
        $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_UPDATE']);
        $paymentOption = $this->getPaymentOptionRepository()->find($id);
        $paymentOption->sortFieldsAscending();

        if ($paymentOption === null) {
            throw $this->createNotFoundException();
        }
        
        $formProfile = $this->createForm(PaymentOptionType::class, $paymentOption, [
            'action' => $this->generateUrl('paymentoption.save', ['id' => $id]),
            'method' => 'POST',
            'id' => $id,
        ]);

        $isPaymentBitcoin = $paymentOption->isPaymentBitcoin();
        if ($isPaymentBitcoin) {
            $bitcoinManager = $this->getBitcoinManager();
            $validationGroups = ['default', 'bitcoinSetting'];
            $bitcoinSettingModel = $bitcoinManager->prepareBitcoinSetting($id);
            $formBitcoinSetting = $this->createForm(BitcoinSettingType::class, $bitcoinSettingModel, [
                'action' => $this->generateUrl('payment.bitcoin_save', ['code' => $paymentOption->getCode()]),
                'method' => 'POST',
                'validation_groups' => $validationGroups,
            ]);

            $dto = $bitcoinManager->createRateSettingsDTO();
            $dto->preserveOriginal();
            $bitcoinRateSettingForm = $this->createForm(BitcoinRateSettingsDTOType::class, $dto, [
                'action' => $this->generateUrl('payment.bitcoin_rate_save'),
                'method' => 'POST',
            ]);
            $bitcoinManager->prepareRateSettingForm($bitcoinRateSettingForm);
            $bitcoinDepositAdjustment = $bitcoinManager->createBitcoinAdjustment(Rate::RATE_EUR, Transaction::TRANSACTION_TYPE_DEPOSIT);
            
            $withdrawalRateDto = $bitcoinManager->createWithdrawalRateSettingsDTO();
            $withdrawalRateDto->preserveOriginal();
            $bitcoinWithdrawalRateSettingForm = $this->createForm(BitcoinWithdrawalRateSettingsDTOType::class, $withdrawalRateDto, [
                'action' => $this->generateUrl('payment.bitcoin_withdrawal_rate_save'),
                'method' => 'POST',
            ]);
            $bitcoinManager->prepareWithdrawalRateSettingForm($bitcoinWithdrawalRateSettingForm);
            $bitcoinWithdrawalAdjustment = $bitcoinManager->createBitcoinAdjustment(Rate::RATE_EUR, Transaction::TRANSACTION_TYPE_WITHDRAW);

            $choiceStatuses = [];
            foreach ($this->getSettingManager()->getSetting('transaction.status') as $statusKey => $status) {
                $choiceStatuses[$status['label']] = $statusKey;
            }

            $formBitcoinConfirmations = $this->createNamedFormTypeBuilder(
                'confirmations',
                \Symfony\Component\Form\Extension\Core\Type\CollectionType::class,
                $this->getBitcoinManager()->getListOfConfirmations(),
                [
                    'entry_type' => \PaymentBundle\Form\BitcoinConfirmationType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'prototype' => true,
                    'prototype_name' => '__fieldname__',
                    'entry_options' => [
                        'transactionStatuses' => $choiceStatuses
                    ],
                    'csrf_protection' => false,
                    'action' => $this->generateUrl('payment.bitcoin_confirmations_save'),
                    'method' => 'POST'
                ]
            )->getForm();
            
            return $this->render('PaymentOptionBundle:PaymentOption:update.html.twig', [
                'formProfile' => $formProfile->createView(),
                'formBitcoinConfiguration' => $formBitcoinSetting->createView(),
                'formRateSetting' => $bitcoinRateSettingForm->createView(),
                'formWithdrawalRateSetting' => $bitcoinWithdrawalRateSettingForm->createView(),
                'withBitcoinConfigurations' => $isPaymentBitcoin,
                'activeTab' => $activeTab,
                'bitcoinAdjustment' => $bitcoinDepositAdjustment,
                'bitcoinWithdrawalAdjustment' => $bitcoinWithdrawalAdjustment,
                'bitcoinConfiguration' => $bitcoinManager->getBitcoinConfigurations(),
                'formBitcoinConfirmation' => $formBitcoinConfirmations->createView(),
            ]);
        } else {
            return $this->render('PaymentOptionBundle:PaymentOption:update.html.twig', [
                'formProfile' => $formProfile->createView(),
                'withBitcoinConfigurations' => $isPaymentBitcoin,
                'activeTab' => $activeTab,
            ]);
        }  
    }

    public function saveAction(Request $request, $id)
    {
        $form = $this->createForm(PaymentOptionType::class);
        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_CREATE']);
            $paymentOption = new \DbBundle\Entity\PaymentOption();
        } else {
            $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_UPDATE']);
            $paymentOption = $this->getPaymentOptionRepository()->find($id);
            $form->remove('code');
        }
        $form->setData($paymentOption);
        $response = ['success' => false];

        try {
            $paymentOption = $this->getManager()->handleCreateForm($form, $request);
            $response['success'] = true;
            $response['data'] = $paymentOption;
        } catch (FormValidationException $e) {
            $response['errors'] = $e->getErrors();
        }

        return $this->response($request, $response, ['groups' => ['Default', '_link']]);
    }

    public function suspendAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_UPDATE']);
        $code = $request->request->get('code');
        $paymentOption = $this->getPaymentOptionRepository()->find($code);
        if (!$paymentOption) {
            throw new NoResultException();
        } else if ($paymentOption->getIsActive()) {
            $paymentOption->suspend();
            $this->getPaymentOptionRepository()->save($paymentOption);
            $message = [
                'type'      => 'success',
                'title'     => $this->getTranslator()->trans('notification.suspended.title', [], 'PaymentOptionBundle'),
                'message'   => $this->getTranslator()->trans('notification.suspended.message', ['%name%' => $paymentOption->getName() . "(" . $paymentOption->getCode() . ")", ], 'PaymentOptionBundle'),
            ];
            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $message);
                return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $message, JsonResponse::HTTP_OK]);
            }
        } else {
            throw new \Exception($paymentOption->getName() . ' is already suspended', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function activateAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_PAYMENTOPTION_UPDATE']);
        $code = $request->request->get('code');
        $paymentOption = $this->getPaymentOptionRepository()->find($code);
        if (!$paymentOption) {
            throw new NoResultException();
        } else if (!$paymentOption->getIsActive()) {
            $paymentOption->enable();
            $this->getPaymentOptionRepository()->save($paymentOption);
            $message = [
                'type'      => 'success',
                'title'     => $this->getTranslator()->trans('notification.activated.title', [], 'PaymentOptionBundle'),
                'message'   => $this->getTranslator()->trans('notification.activated.message', ['%name%' => $paymentOption->getName() . "(" . $paymentOption->getCode() . ")", ], 'PaymentOptionBundle'),
            ];
            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $message);
                return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $message, JsonResponse::HTTP_OK]);
            }
        } else {
            throw new \Exception($paymentOption->getName() . ' is already activated', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    protected function getManager(): \PaymentOptionBundle\Manager\PaymentOptionManager
    {
        return $this->get('paymentoption.manager');
    }

    protected function getPaymentOptionRepository(): \DbBundle\Repository\PaymentOptionRepository
    {
        return $this->getRepository(\DbBundle\Entity\PaymentOption::class);
    }
    
    protected function getBitcoinManager(): BitcoinManager
    {
        return $this->get('payment.bitcoin_manager');
    }
}
