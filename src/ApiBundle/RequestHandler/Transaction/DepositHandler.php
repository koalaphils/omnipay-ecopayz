<?php

declare(strict_types = 1);

namespace ApiBundle\RequestHandler\Transaction;

use ApiBundle\Request\Transaction\DepositRequest;
use AppBundle\Manager\SettingManager;
use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerPaymentOption;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\CustomerPaymentOptionRepository;
use DbBundle\Repository\CustomerProductRepository;
use DbBundle\Repository\PaymentOptionRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use TransactionBundle\Manager\TransactionManager;

class DepositHandler
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var CustomerPaymentOptionRepository
     */
    private $memberPaymentOptionRepository;

    /**
     * @var PaymentOptionRepository
     */
    private $paymentOptionRepository;

    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * @var CustomerProductRepository
     */
    private $customerProductRepository;

    /**
     * @var SettingManager
     */
    private $settingManager;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        TransactionManager $transactionManager,
        CustomerPaymentOptionRepository $memberPaymentOptionRepository,
        PaymentOptionRepository $paymentOptionRepository,
        CustomerProductRepository $customerProductRepository,
        SettingManager $settingManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->memberPaymentOptionRepository = $memberPaymentOptionRepository;
        $this->paymentOptionRepository = $paymentOptionRepository;
        $this->transactionManager = $transactionManager;
        $this->customerProductRepository = $customerProductRepository;
        $this->settingManager = $settingManager;
    }

    public function handle(DepositRequest $depositRequest): Transaction
    {
        $productCode = $this->settingManager->getSetting('pinnacle.product');
        $member = $this->getCurrentMember();
        $memberPaymentOption = $this->getMemberPaymentOption($member, $depositRequest);
        $memberProduct = $this->customerProductRepository->findByCodeAndUsername(['code' => $productCode, 'username' => $member->getPinUserCode()]);

        $transaction = new Transaction();
        $transaction->setCustomer($member);
        $transaction->setPaymentOption($memberPaymentOption);
        $transaction->setType(Transaction::TRANSACTION_TYPE_DEPOSIT);
        $transaction->setNumber($this->transactionManager->generateTransactionNumber('deposit'));
        $transaction->setDate(new \DateTime());
        $transaction->setFee('customer_fee', 0);
        $transaction->setFee('company_fee', 0);
        $transaction->setDetail('email', $depositRequest->getMetaData('field.email'));
        foreach ($depositRequest->getMetaData('payment_details', []) as $key => $value) {
            $transaction->setDetail($key, $value);
        }
        $transaction->autoSetPaymentOptionType();

        $subTransaction = new SubTransaction();
        $subTransaction->setAmount($depositRequest->getAmount());
        $subTransaction->setCustomerProduct($memberProduct);
        $subTransaction->setType(Transaction::TRANSACTION_TYPE_DEPOSIT);


        $transaction->addSubTransaction($subTransaction);
    }

    private function getMemberPaymentOption(Customer $member, DepositRequest $depositRequest): CustomerPaymentOption
    {
        if ($depositRequest->getPaymentOption() !== ''){
            return $this->memberPaymentOptionRepository->find($depositRequest->getPaymentOption());
        }
        $paymentOption = $this->paymentOptionRepository->find($depositRequest->getPaymentOptionType());
        $memberPaymentOption = new CustomerPaymentOption();
        $memberPaymentOption->setPaymentOption($paymentOption);
        $memberPaymentOption->setCustomer($member);
        $memberPaymentOption->addField('account_id', array_get($depositRequest->getMeta(), 'fields.account_id', ''));
        $memberPaymentOption->addField('email', array_get($depositRequest->getMeta(), 'field.email', ''));
        $memberPaymentOption->addField('is_withdrawal', 1);

        return $memberPaymentOption;
    }

    private function getCurrentMember(): Customer
    {
        return $this->tokenStorage->getToken()->getUser()->getCustomer();
    }
}