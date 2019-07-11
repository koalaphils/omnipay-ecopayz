<?php

namespace PaymentBundle\Controller;

use AppBundle\Controller\AbstractController;
use AppBundle\Exceptions\FormValidationException;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\CustomerRepository as MemberRepository;
use PaymentBundle\Component\Blockchain\Rate;
use PaymentBundle\Form\BitcoinConfirmationType;
use PaymentBundle\Form\BitcoinRateSettingsDTOType;
use PaymentBundle\Form\BitcoinWithdrawalRateSettingsDTOType;
use PaymentBundle\Form\BitcoinSettingType;
use PaymentBundle\Manager\BitcoinManager;
use PaymentBundle\Model\Bitcoin\BitcoinConfirmation;
use PaymentBundle\Model\Bitcoin\BitcoinRateSettingsDTO;
use PaymentBundle\Model\MemberToken;
use Payum\Core\Payum;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Payum\Core\Request\Notify;

class BitcoinController extends AbstractController
{
    public function getManager() {}

    public function notifyAction(string $hash): Response
    {
        $token = new MemberToken();
        $token->setHash($hash);
        $token->setDetails(['memberId' => base64_decode($hash)]);
        $token->setGatewayName('bitcoin');

        $gateway = $this->getPayum()->getGateway('bitcoin');
        $gateway->execute(new Notify($token));

        return new Response('', 204);
    }

    public function cycleFundNotifyAction(): Response
    {
        return new Response('*ok*');
    }

    public function settingAction(): Response
    {
        $bitcoinManager = $this->getBitcoinManager();
        $validationGroups = ['default', 'bitcoinSetting'];
        $bitcoinSettingModel = $bitcoinManager->prepareBitcoinSetting();

        $formConfiguration = $this->createForm(BitcoinSettingType::class, $bitcoinSettingModel, [
            'action' => $this->generateUrl('payment.bitcoin_configuration_save'),
            'method' => 'POST',
            'validation_groups' => $validationGroups,
        ]);


        $dto = $bitcoinManager->createRateSettingsDTO();
        $dto->preserveOriginal();
        $bitcoinDepositRateSettingForm = $this->createForm(BitcoinRateSettingsDTOType::class, $dto, [
            'action' => $this->generateUrl('payment.bitcoin_rate_save'),
            'method' => 'POST',
        ]);
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

        return $this->render('PaymentBundle:Bitcoin:setting.html.twig', [
            'configurationForm' => $formConfiguration->createView(),
            'formDepositRateSetting' => $bitcoinDepositRateSettingForm->createView(),
            'formWithdrawalRateSetting' => $bitcoinWithdrawalRateSettingForm->createView(),
            'bitcoinAdjustment' => $bitcoinDepositAdjustment,
            'bitcoinWithdrawalAdjustment' => $bitcoinWithdrawalAdjustment,
            'bitcoinConfiguration' => $bitcoinManager->getBitcoinConfigurations(),
            'formBitcoinConfirmation' => $formBitcoinConfirmations->createView(),
        ]);
    }

    public function saveBitcoinSettingAction(Request $request): Response
    {
        $bitcoinManager = $this->getBitcoinManager();
        $validationGroups = ['default', 'bitcoinSetting'];
        $bitcoinSettingModel = $bitcoinManager->prepareBitcoinSetting();
        $formBitcoinSetting = $this->createForm(BitcoinSettingType::class, $bitcoinSettingModel, [
            'validation_groups' => $validationGroups,
        ]);
        $response = ['success' => false];
        try {
            $bitcoinSetting = $this->getBitcoinManager()->handleCreateBitcoinSettingForm($formBitcoinSetting, $request);
            $response['success'] = true;
            $response['data'] = $bitcoinSetting;
        } catch (FormValidationException $e) {
            $response['errors'] = $e->getErrors();
        }

        return $this->response($request, $response, ['groups' => ['Default', '_link']]);
    }

    public function saveBitcoinRateAction(Request $request): Response
    {
        $bitcoinManager = $this->getBitcoinManager();
        $dto = $bitcoinManager->createRateSettingsDTO();
        $dto->preserveOriginal();
        $bitcoinRateSettingForm = $this->createForm(BitcoinRateSettingsDTOType::class, $dto);
        $response = ['success' => false];
        try {
            $bitcoinRates = $bitcoinManager->handleCreateBitcoinRateForm($bitcoinRateSettingForm, $request, $dto);
            $response['success'] = true;
            $response['data'] = $bitcoinRates;
        } catch (FormValidationException $e) {
            $response['errors'] = $e->getErrors();
        }

        return $this->response($request, $response, ['groups' => ['Default', '_link']]);
    }

    public function saveBitcoinWithdrawalRateAction(Request $request): Response
    {
        $bitcoinManager = $this->getBitcoinManager();
        $dto = $bitcoinManager->createWithdrawalRateSettingsDTO();
        $dto->preserveOriginal();
        $bitcoinWithdrawalRateSettingForm = $this->createForm(BitcoinWithdrawalRateSettingsDTOType::class, $dto);
        $response = ['success' => false];
        try {
            $bitcoinWithdrawalRates = $bitcoinManager->handleCreateBitcoinWithdrawalRateForm($bitcoinWithdrawalRateSettingForm, $request, $dto);
            $response['success'] = true;
            $response['data'] = $bitcoinWithdrawalRates;
        } catch (FormValidationException $e) {
            $response['errors'] = $e->getErrors();
        }

        return $this->response($request, $response, ['groups' => ['Default', '_link']]);
    }

    public function saveBitcoinConfirmationAction(Request $request): Response
    {
        $choiceStatuses = [];
        foreach ($this->getSettingManager()->getSetting('transaction.status') as $statusKey => $status) {
            $choiceStatuses[$status['label']] = $statusKey;
        }

        $formBitcoinConfirmations = $this->createNamedFormTypeBuilder(
            'confirmations',
            CollectionType::class,
            [new BitcoinConfirmation()],
            [
                'entry_type' => BitcoinConfirmationType::class,
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

        $formBitcoinConfirmations->handleRequest($request);

        if ($formBitcoinConfirmations->isSubmitted() && $formBitcoinConfirmations->isValid()) {
            $data = $formBitcoinConfirmations->getData();
            $confirmations = [];
            foreach ($data as $confirmation) {
                $confirmations[] = $confirmation;
            }

            $this->getBitcoinManager()->saveConfirmations($confirmations);

            return new JsonResponse(['success' => true]);
        } else {
            $errors = $this->getBitcoinManager()->getErrorMessages($formBitcoinConfirmations);

            return new JsonResponse(['errors' => $errors], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    private function getBitcoinManager(): BitcoinManager
    {
        return $this->getContainer()->get('payment.bitcoin_manager');
    }

    /**
     * @return Payum
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }

    protected function getMemberRepository(): MemberRepository
    {
        return $this->getRepository(Member::class);
    }
}
