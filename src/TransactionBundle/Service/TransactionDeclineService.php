<?php

namespace TransactionBundle\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\Setting;
use TransactionBundle\Event\TransactionProcessEvent;

class TransactionDeclineService extends AbstractTransactionService
{
    private $interval;

    public function __construct()
    {
        $this->interval = null;
    }

    public function getAutoDeclineStatus(): bool
    {
        $status = false;

        $scheduler = $this->getSettingManager()->getSetting('scheduler');
        if (!empty($scheduler[Setting::SCHEDULER_TASK])) {
            foreach ($scheduler[Setting::SCHEDULER_TASK] as $task => $config) {
                if (isset($config['autoDecline']) && $config['autoDecline'] ) {
                    $this->interval = $config['minutesInterval'];
                    $status = $config['autoDecline'];
                }
            }
        }

        return $status;
    }

    public function processDeclining(): array
    {
        $result = [];
        $status = false;
        $message = null;

        $transactions = $this->getTransactionsToBeDecline();
        if (empty($transactions)) {
            $message = 'No transaction found';
            $this->log($message);
        } else {
            $status = true;
            $result = $this->executeDeclining($transactions);
            $this->reloadTransactionTables();
        }

        return [
            'status' => $status,
            'result' => $result,
            'message' => $message
        ];
    }

    private function getTransactionsToBeDecline(): array
    {
        $filters['interval'] = $this->interval . ' ' . Setting::TIME_DURATION_NAME ;
        $filters['status'] = Transaction::TRANSACTION_STATUS_ACKNOWLEDGE;
        $filters['type'] = Transaction::TRANSACTION_TYPE_DEPOSIT;
        $filters['paymentOptionType'] = $this->getPaymentOptionWithAutoDeclineTag();

        return $this->getTransactionRepository()->getTransactions($filters);
    }

    private function executeDeclining(array $transactions = []): array
    {
        $this->log('Executing on voiding transaction...');
        $transactionIds = [];
        foreach ($transactions as $item) {
            $transactionIds[] = $item->getId();
            $this->log('Decline transaction number: ' . $item->getNumber());
            $transaction = $this->getDbTransactionRepository()->find($item->getId());
            $transaction->setReasonToVoidOrDecline('No deposit received in payment gateway');
            $transaction->decline();
            $this->getDbTransactionRepository()->save($transaction);
            $eventTransactionDecline = new TransactionProcessEvent($transaction);
            $this->getEventDispatcher()->dispatch('transaction.autoDeclined', $eventTransactionDecline);
        }

        return $transactionIds;
    }

    
    /*
    * return array Array of DbBundle/Entity/PaymentOption
    */
    private function getPaymentOptionWithAutoDeclineTag(): array
    {
        $paymentOption = $this->getPaymentOptionRepository();

        $paymentOptionToDecline = [];
        foreach ($paymentOption->getAutoDeclineTag() as $key => $item) {
            $paymentOptionToDecline[] = $item['code'];
        }

        return $paymentOptionToDecline;
    }

    private function getDbTransactionRepository(): \DbBundle\Repository\TransactionRepository
    {
        return $this->getEntityManager()->getRepository(Transaction::class);
    }
}
