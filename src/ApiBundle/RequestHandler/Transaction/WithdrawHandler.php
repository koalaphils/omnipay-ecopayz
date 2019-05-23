<?php

declare(strict_types = 1);

namespace ApiBundle\RequestHandler\Transaction;

use ApiBundle\Request\Transaction\WithdrawRequest;
use AppBundle\Manager\SettingManager;
use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerPaymentOption;
use DbBundle\Entity\PaymentOption;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\CustomerPaymentOptionRepository;
use DbBundle\Repository\CustomerProductRepository;
use DbBundle\Repository\PaymentOptionRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use TransactionBundle\Manager\TransactionManager;

class WithdrawHandler
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
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

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
        EntityManager $entityManager,
        EventDispatcherInterface $eventDispatcher,
        SettingManager $settingManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->memberPaymentOptionRepository = $memberPaymentOptionRepository;
        $this->paymentOptionRepository = $paymentOptionRepository;
        $this->transactionManager = $transactionManager;
        $this->customerProductRepository = $customerProductRepository;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->settingManager = $settingManager;
    }

    public function handle(WithdrawRequest $withdrawRequest): Transaction
    {
        try {
            $this->entityManager->beginTransaction();
            $member = $this->getCurrentMember();
            $memberPaymentOption = $this->getMemberPaymentOption($member, $withdrawRequest);
            $email = $withdrawRequest->getMeta()->getFields()->getEmail();

            $transaction = new Transaction();
            $transaction->setCustomer($member);
            $transaction->setPaymentOption($memberPaymentOption);
            $transaction->setType(Transaction::TRANSACTION_TYPE_WITHDRAW);
            $transaction->setNumber($this->transactionManager->generateTransactionNumber('withdraw'));
            $transaction->setDate(new \DateTime());
            $transaction->setFee('customer_fee', 0);
            $transaction->setFee('company_fee', 0);
            $transaction->setDetail('email', $email);
            foreach ($withdrawRequest->getMeta()->getPaymentDetailsAsArray() as $key => $value) {
                $transaction->setDetail($key, $value);
            }

            if ($withdrawRequest->getPaymentOptionType() === $this->settingManager->getSetting('bitcoin.setting.paymentOption')) {
                $transaction->setBitcoinAddress($withdrawRequest->getMeta()->getFields()->getAccountId());
            }

            foreach ($withdrawRequest->getProducts() as $productInfo) {
                $memberProduct = $this->customerProductRepository->findByUsernameProductCodeAndCurrencyCode(
                    $productInfo->getUsername(),
                    $productInfo->getProductCode(),
                    $member->getCurrencyCode()
                );
                $subTransaction = new SubTransaction();
                $subTransaction->setAmount($productInfo->getAmount());
                $subTransaction->setCustomerProduct($memberProduct);
                $subTransaction->setType(Transaction::TRANSACTION_TYPE_WITHDRAW);
                foreach ($productInfo->getMeta()->getPaymentDetailsAsArray() as $key => $value) {
                    $subTransaction->setDetail($key, $value);
                }

                $transaction->addSubTransaction($subTransaction);
            }
            $transaction->setPaymentOptionOnTransaction($memberPaymentOption);
            $transaction->retainImmutableData();

            $action = ['label' => 'Save', 'status' => Transaction::TRANSACTION_STATUS_START];
            $this->transactionManager->processTransaction($transaction, $action, true);

            $this->entityManager->commit();

            return $transaction;
        } catch (\Exception $exception) {
            @$this->entityManager->rollback();

            throw $exception;
        }
    }

    private function getMemberPaymentOption(Customer $member, WithdrawRequest $withdrawRequest): CustomerPaymentOption
    {
        if ($withdrawRequest->getPaymentOption() !== ''){
            return $this->memberPaymentOptionRepository->find($withdrawRequest->getPaymentOption());
        }
        $fields = array_get($withdrawRequest->getMeta()->toArray(), 'fields', []);
        $fields['is_withdrawal'] = 1;

        $memberPaymentOption = $this->memberPaymentOptionRepository->findByFields((int) $member->getId(), $withdrawRequest->getPaymentOptionType(), $fields);
        if ($memberPaymentOption instanceof CustomerPaymentOption) {
            return $memberPaymentOption;
        }

        $paymentOption = $this->paymentOptionRepository->find($withdrawRequest->getPaymentOptionType());
        $memberPaymentOption = new CustomerPaymentOption();
        $memberPaymentOption->setPaymentOption($paymentOption);
        $memberPaymentOption->setCustomer($member);
        $memberPaymentOption->addField('account_id', array_get($withdrawRequest->getMeta()->toArray(), 'fields.account_id', ''));
        $memberPaymentOption->addField('email', array_get($withdrawRequest->getMeta()->toArray(), 'fields.email', ''));
        $memberPaymentOption->addField('is_withdrawal', 1);

        $this->entityManager->persist($memberPaymentOption);
        $this->entityManager->flush($memberPaymentOption);

        return $memberPaymentOption;
    }

    private function getCurrentMember(): Customer
    {
        return $this->tokenStorage->getToken()->getUser()->getCustomer();
    }
}