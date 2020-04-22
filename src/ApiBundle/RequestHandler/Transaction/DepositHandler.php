<?php

declare(strict_types = 1);

namespace ApiBundle\RequestHandler\Transaction;

use ApiBundle\Event\TransactionCreatedEvent;
use ApiBundle\Request\Transaction\DepositRequest;
use AppBundle\Manager\SettingManager;
use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerPaymentOption;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\CustomerPaymentOptionRepository;
use DbBundle\Repository\CustomerProductRepository;
use DbBundle\Repository\PaymentOptionRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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

    public function handle(DepositRequest $depositRequest): Transaction
    {
        try {
            $this->entityManager->beginTransaction();
            $member = $this->getCurrentMember();
            $memberPaymentOption = $this->getMemberPaymentOption($member, $depositRequest);

            $email = $depositRequest->getMeta()->getFields()->getEmail();

            $transaction = new Transaction();
            $transaction->setCustomer($member);
            $transaction->setPaymentOption($memberPaymentOption);
            $transaction->setType(Transaction::TRANSACTION_TYPE_DEPOSIT);
            $transaction->setNumber($this->transactionManager->generateTransactionNumber('deposit'));
            $transaction->setDate(new \DateTime());
            $transaction->setFee('customer_fee', 0);
            $transaction->setFee('company_fee', 0);
            $transaction->setDetail('email', $email);
            foreach ($depositRequest->getMeta()->getPaymentDetailsAsArray() as $key => $value) {
                $transaction->setDetail($key, $value);
            }
            $transaction->autoSetPaymentOptionType();

            foreach ($depositRequest->getProducts() as $productInfo) {
                $memberProduct = $this->customerProductRepository->findByUsernameProductCodeAndCurrencyCode(
                    $productInfo->getUsername(),
                    $productInfo->getProductCode(),
                    $member->getCurrencyCode()
                );
                $subTransaction = new SubTransaction();
                $subTransaction->setAmount($productInfo->getAmount());
                $subTransaction->setCustomerProduct($memberProduct);
                $subTransaction->setType(Transaction::TRANSACTION_TYPE_DEPOSIT);
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

            $event = new TransactionCreatedEvent($transaction);
            $this->eventDispatcher->dispatch('transaction.created', $event);

            return $transaction;
        } catch (\Exception $exception) {
            @$this->entityManager->rollback();

            throw $exception;
        }

    }

    private function getMemberPaymentOption(Customer $member, DepositRequest $depositRequest): CustomerPaymentOption
    {
        if ($depositRequest->getPaymentOption() !== ''){
            return $this->memberPaymentOptionRepository->find($depositRequest->getPaymentOption());
        }

        $fields = array_get($depositRequest->getMeta()->toArray(), 'fields', []);
        $fields['is_deposit'] = 1;

        unset($fields['account_id']);

        if ($this->isBitcoin($depositRequest->getPaymentOptionType())) {
            unset($fields['email']);
        }

        $memberPaymentOption = $this->memberPaymentOptionRepository->findByFields((int) $member->getId(), $depositRequest->getPaymentOptionType(), $fields);

        if ($memberPaymentOption instanceof CustomerPaymentOption) {
            return $memberPaymentOption;
        }

        $paymentOption = $this->paymentOptionRepository->find($depositRequest->getPaymentOptionType());
        $memberPaymentOption = new CustomerPaymentOption();
        $memberPaymentOption->setPaymentOption($paymentOption);
        $memberPaymentOption->setCustomer($member);
        $memberPaymentOption->addField('account_id', array_get($depositRequest->getMeta()->toArray(), 'fields.account_id', ''));
        $memberPaymentOption->addField('email', array_get($depositRequest->getMeta()->toArray(), 'fields.email', ''));
        $memberPaymentOption->setForDeposit();

        if (!$this->isBitcoin($depositRequest->getPaymentOptionType())) {
            $memberPaymentOption->setForWithdrawal();
        }

        $this->entityManager->persist($memberPaymentOption);
        $this->entityManager->flush($memberPaymentOption);

        return $memberPaymentOption;
    }

    private function getCurrentMember(): Customer
    {
        return $this->tokenStorage->getToken()->getUser()->getCustomer();
    }

    private function isBitcoin(string $paymentOption): bool
    {
        return $paymentOption === $this->settingManager->getSetting('bitcoin.setting.paymentOption');
    }
}