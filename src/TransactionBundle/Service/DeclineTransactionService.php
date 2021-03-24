<?php

declare(strict_types = 1);

namespace TransactionBundle\Service;

use AppBundle\Manager\SettingManager;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\PaymentOptionRepository;
use DbBundle\Repository\TransactionRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TransactionBundle\Event\TransactionPostDeclineEvent;
use TransactionBundle\Event\TransactionPreDeclineEvent;
use TransactionBundle\Manager\TransactionManager;

class DeclineTransactionService
{
    /**
     * @var SettingManager
     */
    private $settingManager;

    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * @var PaymentOptionRepository
     */
    private $paymentOptionRepository;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var TransactionManager
     */
    private $transactionManager;

    public function __construct(
        SettingManager $settingManager,
        TransactionRepository $transactionRepository,
        PaymentOptionRepository $paymentOptionRepository,
        EventDispatcherInterface $eventDispatcher,
        TransactionManager $transactionManager
    ) {
        $this->settingManager = $settingManager;
        $this->transactionRepository = $transactionRepository;
        $this->paymentOptionRepository = $paymentOptionRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->transactionManager = $transactionManager;
    }

    public function getAutoDeclineConfiguration(): array
    {
        return $this->settingManager->getSetting('scheduler.task.auto_decline');
    }

    public function declineTransactions(): array
    {
        $configurations = $this->getAutoDeclineConfiguration();
        $interval = $configurations['minutesInterval'];
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $paymentOptions = $this->paymentOptionRepository->getPaymentOptionForAutoDecline();
        $paymentOptionCodes = [];
        $statuses = [$configurations['status']];
        $types = $configurations['types'];
        foreach ($paymentOptions as $paymentOption) {
            $paymentOptionCodes[] = $paymentOption->getCode();
            $paymentOptionStatus = $paymentOption->getConfiguration('autoDecline.status', $configurations['status']);
            $paymentOptionTypes = $paymentOption->getConfiguration('autoDecline.types', []);
            if (!in_array($paymentOptionStatus, $statuses)) {
                $statuses[] = $paymentOptionStatus;
            }

            foreach ($paymentOptionTypes as $paymentOptionType) {
                if (!in_array($paymentOptionType, $types)) {
                    $types[] = $paymentOptionType;
                }
            }
        }

        $transactions = $this->transactionRepository->getTransactionsByStatusAndType(
            $statuses,
            $types,
            $paymentOptionCodes
        );
        $transactionIds = [];
        foreach ($transactions as $transaction) {
            if (!$transaction->getPaymentOptionType()->hasAutoDecline()) {
                continue;
            }

            $paymentOptionStatus = (int) $transaction->getPaymentOptionType()->getConfiguration('autoDecline.status', $configurations['status']);
            if ($paymentOptionStatus !== ((int) $transaction->getStatus())) {
                continue;
            }

            $paymentOptionTypes = $transaction->getPaymentOptionType()->getConfiguration('autoDecline.types', $configurations['types']);
            if (!in_array($transaction->getType(), $paymentOptionTypes)) {
                continue;
            }

            $intervalExpiration = $transaction->getPaymentOptionType()->getConfiguration('autoDecline.interval', $interval);

            $expiration = \DateTimeImmutable::createFromMutable($transaction->getUpdatedAt())->modify('+' . $intervalExpiration . ' minutes');
            $expiration->setTimezone(new \DateTimeZone('UTC'));

            if ($expiration < $now) {
                if ($this->declineTransaction($transaction, $configurations['reason'][$transaction->getType()])) {
                    $transactionIds[$transaction->getId()] = $transaction->getNumber();
                }
            }
        }

        return $transactionIds;
    }

    public function declineTransaction(Transaction $transaction, string $reason): bool
    {
        $eventPre = new TransactionPreDeclineEvent($transaction);
        $this->eventDispatcher->dispatch('transaction.autoDeclined.pre', $eventPre);
        if ($eventPre->isPropagationStopped()) {
            return false;
        }

        $transaction->setReasonToVoidOrDecline($reason);
        $this->transactionManager->processTransaction($transaction, 'decline');
        $eventPost = new TransactionPostDeclineEvent($transaction);

        $this->eventDispatcher->dispatch('transaction.autoDeclined.post', $eventPost);

        return true;
    }
}