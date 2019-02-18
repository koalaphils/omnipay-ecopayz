<?php

namespace PaymentBundle\Controller;

use AppBundle\Controller\AbstractController;
use AppBundle\Exceptions\FormValidationException;
use DbBundle\Entity\Customer as Member;
use DbBundle\Repository\CustomerRepository as MemberRepository;
use PaymentBundle\Form\BitcoinConfirmationType;
use PaymentBundle\Form\BitcoinRateSettingsDTOType;
use PaymentBundle\Form\BitcoinSettingType;
use PaymentBundle\Manager\BitcoinManager;
use PaymentBundle\Model\Bitcoin\BitcoinConfirmation;
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

        // $detail = $token->getDetails();
        // return new JsonResponse([600, $detail]);

        $gateway = $this->getPayum()->getGateway('bitcoin');
        $notify = new Notify($token);        
        $gateway->execute($notify);
        return new Response('', 204);
    }

    public function cycleFundNotifyAction(): Response
    {
        return new Response('*okk*');
    }

    public function saveBitcoinSettingAction(Request $request): Response
    {
        $bitcoinManager = $this->getBitcoinManager();
        $validationGroups = ['default', 'bitcoinSetting'];
        $bitcoinConfiguration = $bitcoinManager->getBitcoinConfiguration();
        $bitcoinLockDownSetting = $bitcoinManager->getBitcoinLockDownRateSetting();
        $bitcoinSettingModel = $bitcoinManager->prepareBitcoinSetting($bitcoinConfiguration, $bitcoinLockDownSetting);
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
