<?php

namespace TransactionBundle\Service;

use DbBundle\Entity\Transaction;
use DbBundle\Repository\UserRepository;
use DbBundle\Repository\TransactionRepository;
use DbBundle\Repository\PaymentOptionRepository;
use DbBundle\Entity\User;
use DbBundle\Entity\PaymentOption;
use DbBundle\Entity\Setting;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Console\Logger\ConsoleLogger;
use PaymentBundle\Manager\BitcoinManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use PaymentBundle\Model\Bitcoin\SettingModel;
use TransactionBundle\Event\TransactionProcessEvent;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

class TransactionDeclineService extends AbstractTransactionService
{
    private $interval;
    public function __construct()
    {
        $this->interval = null;
    }

    public function setAutoDeclineLogger($logger): void
    {
        $this->setLogger($logger);
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

    public function createHttpRequest(): Request
    {
        $request = Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $this->getRequestStack()->push($request);

        return $request;
    }

    public function loginUserByUsername(string $username = null): User
    {
        $user = $this->getUserRepository()->loadUserByUsername($username);
        if ($user === null) {
            throw new UsernameNotFoundException('User not found');
        }
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->getSecurityToken()->setToken($token);

        #$event = new InteractiveLoginEvent(new Request(), $token);
        #$this->getEventDispatcher()->dispatch('security.interactive_login', $event);

        return $user;
    }
    
    public function setLoggerForUser(User $user, ConsoleLogger $logger): void
    {
        $roles = $user->getRoles();
        
        $logger->info(sprintf('Login User: %s [%s]', $user->getUsername(), $user->getId()));
    }
    
    public function getBitcoinAutoDeclineStatus(): bool
    {
        return $this->getBitcoinManager()->getBitcoinAutoDeclineStatus();
    }

    public function declineBitcoinTransactions(): void
    {
        $transactions = $this->getBitcoinTransactionsToBeDecline();
        if (empty($transactions)) {
            $this->log('No transaction found');
        } else {
            $result = $this->decline($transactions);
            $this->log('Declined Ids: ' . json_encode($result));
            $this->reloadTransactionTables($result);
        }
    }

    public function declineTransactions(): void
    {
        $transactions = $this->getTransactionsToBeDecline();
        if (empty($transactions)) {
            $this->log('No transaction found');
        } else {
            $result = $this->decline($transactions);
            $this->log('Declined Ids: ' . json_encode($result));
            $this->reloadTransactionTables($result);
        }
    }

    private function getBitcoinTransactionsToBeDecline(): array
    {
        $result = [];
        $bitcoinConfiguration = $this->getBitcoinManager()->getBitcoinConfiguration();
        $hasBitcoinPaymentOptionActivated = $this->getBitcoinPaymentOptionWithAutoDeclineEnabled();
        if (!empty($hasBitcoinPaymentOptionActivated)) {
            $result = $this->getTransactionRepository()->getTransactionsToDecline(
                $bitcoinConfiguration['minutesInterval'] . ' ' . SettingModel::BITCOIN_TIME_DURATION_NAME,
                Transaction::TRANSACTION_STATUS_START,
                Transaction::TRANSACTION_TYPE_DEPOSIT,
                $hasBitcoinPaymentOptionActivated
            );
        }

        return $result;
    }
    
    private function getTransactionsToBeDecline(): array
    {   
        $result = [];
        $paymentOptions = $this->getPaymentOptionWithAutoDeclineEnabled();
        if (!empty($paymentOptions)) {
            $result = $this->getTransactionRepository()->getTransactionsToDecline(
                $this->interval . ' ' . Setting::TIME_DURATION_NAME,
                Transaction::TRANSACTION_STATUS_ACKNOWLEDGE,
                Transaction::TRANSACTION_TYPE_DEPOSIT,
                $paymentOptions
            );
        }

        return $result;
    }

    public function decline(array $transactions): array
    {
        $affectedTransactionIds = [];
        $this->log('Executing on declining transaction...');
        foreach ($transactions as $key => $index) {
            $affectedTransactionIds[] = $index['id'];
            $transaction = $this->getTransactionRepository()->find($index['id']);
            $this->log('Decline transaction number: ' . $transaction->getNumber());
            $transaction->setReasonToVoidOrDecline('No deposit received in payment gateway');
            $transaction->decline();
            
            $eventTransactionDecline = new TransactionProcessEvent($transaction);
            $this->getEventDispatcher()->dispatch('transaction.autoDeclined', $eventTransactionDecline);

            $this->getTransactionRepository()->save($transaction);
        }

        return $affectedTransactionIds;
    }

    private function getBitcoinPaymentOptionWithAutoDeclineEnabled(): array
    {
        $paymentOptionToDecline = [];

        $paymentOption = $this->getPaymentOptionRepository()->getBitcoinPaymentOptionCodeHasWithAutoDeclineEnabled();
        foreach ($paymentOption as $key => $item) {
            $paymentOptionToDecline[] = $item['code'];
        }

        return $paymentOptionToDecline;
    }
    
    private function getPaymentOptionWithAutoDeclineEnabled(): array
    {
        $paymentOptionToDecline = [];
        $paymentOption = $this->getPaymentOptionRepository()->getEnabledAutoDecline();
        foreach ($paymentOption as $key => $item) {
            if ($item['code'] == strtoupper(PaymentOption::PAYMENT_MODE_BITCOIN)) {
                continue;
            }
            $paymentOptionToDecline[] = $item['code'];
        }

        return $paymentOptionToDecline;
    }

    private function getRequestStack(): RequestStack
    {
        return $this->container->get('request_stack');
    }

    private function getBitcoinManager(): BitcoinManager
    {
        return $this->container->get('payment.bitcoin_manager');
    }
    
    private function getSecurityToken(): TokenStorage
    {
        return $this->container->get('security.token_storage');
    }
}