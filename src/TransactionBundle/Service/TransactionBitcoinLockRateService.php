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
use TransactionBundle\Event\BitcoinRateExpiredEvent;

class TransactionBitcoinLockRateService extends AbstractTransactionService
{
    private $interval;
    public function __construct()
    {
        $this->interval = null;
    }

    public function setAutoLockLogger($logger): void
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

        $event = new InteractiveLoginEvent(new Request(), $token);
        $this->getEventDispatcher()->dispatch('security.interactive_login', $event);

        return $user;
    }
    
    public function setLoggerForUser(User $user, ConsoleLogger $logger): void
    {
        $roles = $user->getRoles();

        if (!in_array('role.bitcoin.setting', $roles)) {
            throw new \Exception('Access Denied.');
        }
        
        $logger->info(sprintf('Login User: %s [%s]', $user->getUsername(), $user->getId()));
    }
    
    public function getBitcoinAutoLockRateStatus(): bool
    {
        return $this->getBitcoinManager()->getBitcoinAutoLockDownRateStatus();
    }

    public function lockBitcoinTransactions(): void
    {
        $transactions = $this->getBitcoinTransactionsToLock();
        if (empty($transactions)) {
            $this->log('No transaction found');
        } else {
            $result = $this->lockTransactions($transactions);
            $this->log('Locked Ids: ' . json_encode($result));
            $this->reloadTransactionTables($result);
        }
    }

    public function lockBitcoinTransaction(Transaction $transaction): void
    {
        if (!$this->getBitcoinAutoLockRateStatus()) {
            return;
        }

        $result = $this->lockTransactions([
            0 => [
                'id' => $transaction->getId(),
            ]
        ]);
        $this->log('Locked Ids: ' . json_encode($result));
        $this->reloadTransactionTables($result);
    }

    private function getBitcoinTransactionsToLock(): array
    {
        $result = [];
        $bitcoinLockDownRateSetting = $this->getBitcoinManager()->getBitcoinLockDownRateSetting();
        $hasBitcoinPaymentOption = $this->getBitcoinPaymentOption();
        if (!empty($hasBitcoinPaymentOption)) {
            $result = $this->getTransactionRepository()->getBitcoinTransactionsToLock(
                $bitcoinLockDownRateSetting['minutesLockDownInterval'] . ' ' . SettingModel::BITCOIN_TIME_DURATION_NAME
            );
        }

        return $result;
    }

    public function lockTransactions(array $transactions): array
    {
        $affectedTransactionIds = [];
        $this->log('Executing on locking transaction...');
        foreach ($transactions as $key => $index) {
            $affectedTransactionIds[] = $index['id'];
            $transaction = $this->getTransactionRepository()->find($index['id']);
            $this->log('Decline transaction number: ' . $transaction->getNumber());
            $transaction->setBitcoinRateExpired();
            
            $event = new BitcoinRateExpiredEvent($transaction);
            $this->getEventDispatcher()->dispatch(BitcoinRateExpiredEvent::NAME, $event);

            $this->getTransactionRepository()->save($transaction);
        }

        return $affectedTransactionIds;
    }

    private function getBitcoinPaymentOption(): array
    {
        $paymentOptionToLock = [];

        $paymentOption = $this->getPaymentOptionRepository()->getBitcoinPaymentOptionCode();
        foreach ($paymentOption as $key => $item) {
            $paymentOptionToLock[] = $item['code'];
        }

        return $paymentOptionToLock;
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