<?php

namespace PaymentBundle\Manager;

use AppBundle\Exceptions\FormValidationException;
use AppBundle\Helper\Publisher;
use AppBundle\Manager\AbstractManager;
use AppBundle\Manager\SettingManager;
use DbBundle\Entity\BitcoinRateSetting;
use DbBundle\Entity\User;
use DbBundle\Entity\Setting;
use DbBundle\Entity\Transaction;
use DbBundle\Utils\CollectionUtils;
use Doctrine\ORM\EntityManagerInterface;
use PaymentBundle\Component\Bitcoin\BitcoinAdjustmentInterface;
use PaymentBundle\Component\Blockchain\BlockChainInterface;
use PaymentBundle\Component\Model\BitcoinAdjustment;
use ApiBundle\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Request;
use PaymentBundle\Component\Blockchain\Rate;
use PaymentBundle\Event\BitcoinRateSettingSaveEvent;
use PaymentBundle\Model\Bitcoin\BitcoinConfirmation;
use PaymentBundle\Model\Bitcoin\BitcoinRateSettingsDTO;
use PaymentBundle\Model\Bitcoin\SettingModel;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Form;
use TransactionBundle\Event\TransactionProcessEvent;

class BitcoinManager extends AbstractManager
{
    private const BITCOIN_EXCHANGE_RATE_CHANNEL = 'btc.exchange_rate';
    private $settingManager;
    private $em;
    private $dispatcher;

    private $userRepository;
    private $transactionRepository;

    private $bitcoinAdjustmentComponent;
    private $blockchain;
    private $publisher;

    public function __construct(SettingManager $settingManager, EntityManagerInterface $em, EventDispatcherInterface $dispatcher, TransactionRepository $transactionRepository)
    {
        $this->settingManager = $settingManager;
        $this->em = $em;
        $this->dispatcher = $dispatcher;

        $this->transactionRepository = $transactionRepository;
        $this->userRepository = $this->em->getRepository(User::class);
    }

    public function getRepository() {}

    public function handleCreateBitcoinSettingForm(Form $form, Request $request)
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $bitcoinConfiguration[SettingModel::BITCOIN_DEPOSIT_CONFIGURATION] = [
                'autoDecline' => $data->getAutoDecline(),
                'minutesInterval' => $data->getMinutesInterval(),
                'minimumAllowedDeposit' => $data->getMinimumAllowedDeposit(),
                'maximumAllowedDeposit' => $data->getMaximumAllowedDeposit(),
            ];
            $bitcoinConfiguration[SettingModel::BITCOIN_LOCK_PERIOD_SETTING] = [
                'autoLock' => $data->getAutoLock(),
                'minutesLockDownInterval' => $data->getMinutesLockDownInterval(),
            ];
            $bitcoinConfiguration[SettingModel::BITCOIN_WITHDRAWAL_CONFIGURATION] = [
                'minimumAllowedWithdrawal' => $data->getMinimumAllowedWithdrawal(),
                'maximumAllowedWithdrawal' => $data->getMaximumAllowedWithdrawal(),
            ];
            $this->saveBitcoinSetting($bitcoinConfiguration);

            return $data;
        }

        throw new FormValidationException($form);
    }

    public function handleCreateBitcoinRateForm(Form $form, Request $request, BitcoinRateSettingsDTO $dto)
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveRateSettings($dto, Transaction::TRANSACTION_TYPE_DEPOSIT);

            return $dto;
        }

        throw new FormValidationException($form);
    }

    public function handleCreateBitcoinWithdrawalRateForm(Form $form, Request $request, BitcoinRateSettingsDTO $dto)
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveRateSettings($dto, Transaction::TRANSACTION_TYPE_WITHDRAW);

            return $dto;
        }

        throw new FormValidationException($form);
    }

    public function getBitcoinConfigurations(): array
    {        
        $bitcoinConfigurations = $this->getBitcoinConfiguration();
        $bitcoinConfigurations = array_merge($bitcoinConfigurations, $this->getBitcoinLockDownRateSetting());
        $bitcoinConfigurations = array_merge($bitcoinConfigurations, $this->getBitcoinWithdrawalConfiguration());
        
        return $bitcoinConfigurations;
    }

    public function getBitcoinConfiguration(): array
    {
        $bitcoinSettings = $this->getBitcoinSettings();
        $configurations = $this->settingManager->getSetting('bitcoin.' . SettingModel::BITCOIN_SETTING . '.' . SettingModel::BITCOIN_DEPOSIT_CONFIGURATION);

        return $configurations;
    }

    public function getBitcoinLockDownRateSetting(): array
    {
        $bitcoinSettings = $this->getBitcoinSettings();
        $lockDownSetting = $this->settingManager->getSetting('bitcoin.' . SettingModel::BITCOIN_SETTING . '.' . SettingModel::BITCOIN_LOCK_PERIOD_SETTING);

        return $lockDownSetting;
    }

    public function getBitcoinWithdrawalConfiguration(): array
    {
        $bitcoinSettings = $this->getBitcoinSettings();
        $withdrawalConfigurations = $this->settingManager->getSetting('bitcoin.' . SettingModel::BITCOIN_SETTING . '.' . SettingModel::BITCOIN_WITHDRAWAL_CONFIGURATION);

        return $withdrawalConfigurations;
    }

    public function getBitcoinSettings(): array
    {
        return $this->settingManager->getSetting('bitcoin');
    }

    public function getBitcoinAutoDeclineStatus(): bool
    {
        $bitcoinConfiguration = $this->getBitcoinConfiguration();

        return $bitcoinConfiguration['autoDecline'];
    }

    public function getBitcoinAutoLockDownRateStatus(): bool
    {
        $bitcoinLockConfiguration = $this->getBitcoinLockDownRateSetting();

        return $bitcoinLockConfiguration['autoLock'];
    }

    public function prepareBitcoinSetting(array $bitcoinSetting = []): SettingModel
    {
        $bitcoinSettingModel = new SettingModel();
        $bitcoinSettingModel->setAutoDecline($bitcoinSetting['autoDecline']);
        $bitcoinSettingModel->setMinutesInterval($bitcoinSetting['minutesInterval']);
        $bitcoinSettingModel->setMinimumAllowedDeposit($bitcoinSetting['minimumAllowedDeposit']);
        $bitcoinSettingModel->setMaximumAllowedDeposit($bitcoinSetting['maximumAllowedDeposit']);
        // zimi-fix
        if (array_key_exists('autoLock', $bitcoinSetting)) {
            $bitcoinSettingModel->setAutoLock($bitcoinSetting['autoLock']);
        }
        if (array_key_exists('minutesLockDownInterval', $bitcoinSetting)) {
            $bitcoinSettingModel->setMinutesLockDownInterval($bitcoinSetting['minutesLockDownInterval']);
        }

        if (array_key_exists('maximumAllowedWithdrawal', $bitcoinSetting)) {
            $bitcoinSettingModel->setMaximumAllowedWithdrawal($bitcoinSetting['maximumAllowedWithdrawal']);
        } 
        if (array_key_exists('minimumAllowedWithdrawal', $bitcoinSetting)) {
            $bitcoinSettingModel->setMinimumAllowedWithdrawal($bitcoinSetting['minimumAllowedWithdrawal']);
        }        
        
        return $bitcoinSettingModel;
    }

    public function createBitcoinAdjustment(string $currency, int $type = Transaction::TRANSACTION_TYPE_DEPOSIT): BitcoinAdjustment
    {
        $cache = new FilesystemAdapter();
        $cacheItem = $cache->getItem('bitcoin.rate_data');

        $rateComponent = $this->getBlockchain()->getRate();
        $rate = $rateComponent->fromBTC($currency, '1');

        $config = $this->getBitcoinConfigurations();

        $bitcoinAdjustment = $this->getBitcoinAdjustmentComponent()->getAdjustment($type);
        $bitcoinAdjustment->setLatestBaseRate($rate);
        $bitcoinAdjustment->setBitcoinConfig($config);

        // Override cache whenever a new value is generated.
        $cacheItem->set($bitcoinAdjustment->createWebsocketPayload($type));
        $cache->save($cacheItem);

        return $bitcoinAdjustment;
    }

    public function saveBitcoinSetting(array $configuration = []): void
    {
        $setting = $this->settingManager->getSetting('bitcoin.' . SettingModel::BITCOIN_SETTING);
        foreach ($configuration as $key => $value) {
            array_set($setting, $key, $value);
        }
        $this->settingManager->saveSetting('bitcoin.' . SettingModel::BITCOIN_SETTING, $setting);
    }
    
    public function publishAdjustedBtcExchangeRate(string $exchangeRateData): void
    {
        $this->publisher->publishUsingWamp(self::BITCOIN_EXCHANGE_RATE_CHANNEL, json_decode($exchangeRateData,true));
    }

      /**
     * @param array $listOfBitcoinConfirmations List of BitcoinConfirmation
     */
    public function saveConfirmations(array $listOfBitcoinConfirmations): void
    {
        $totalConfirmation = count($listOfBitcoinConfirmations);
        $confirmations = [];
        foreach ($listOfBitcoinConfirmations as $key => $confirmation) {
            if ($confirmation instanceof BitcoinConfirmation) {
                $confirmation->setConfirmationNumber($key);
                $confirmations[(string) $key] = $confirmation->toArray();
            }
        }

        $this->getSettingManager()->saveSetting('bitcoin.confirmations', $confirmations);
    }

    public function getListOfConfirmations(): array
    {
        $confirmations = $this->getSettingManager()->getSetting('bitcoin.confirmations', []);

        return array_map(function ($confirmation) {
            return BitcoinConfirmation::create($confirmation['num'], $confirmation['label'], $confirmation['transactionStatus']);
        }, $confirmations);
    }

    public function getMaxConfirmation(): int
    {
        return count($this->getSettingManager()->getSetting('bitcoin.confirmations', [])) - 1;
    }

    public function setPublisher(Publisher $publisher): void
    {
        $this->publisher = $publisher;
    }

    private function getSettingManager(): SettingManager
    {
        return $this->settingManager;
    }

    public function getDefaultRateSetting(int $type = Transaction::TRANSACTION_TYPE_DEPOSIT): BitcoinRateSetting
    {
        $defaultRateSetting = $this->em->getRepository(BitcoinRateSetting::class)->findDefaultSetting($type);

        if ($defaultRateSetting === null) {
            $defaultRateSetting = new BitcoinRateSetting();
            $defaultRateSetting->setIsDefault(true);
            $defaultRateSetting->setType($type);
        }

        $this->setLastTouchedDetails($defaultRateSetting);

        return $defaultRateSetting;
    }
    
    public function getDefaultWithdrawalRateSetting(): BitcoinRateSetting
    {
        return $this->getDefaultRateSetting(Transaction::TRANSACTION_TYPE_WITHDRAW);
    }

    public function getNonDefaultRateSettings(int $type = Transaction::TRANSACTION_TYPE_DEPOSIT): array
    {
        $rateSettings = $this->em->getRepository(BitcoinRateSetting::class)->findNonDefaultRateSettings($type);

        // If $rateSettings is currently empty. Then create a logical first rate setting.
        if (empty($rateSettings)) {
            $rateSetting = new BitcoinRateSetting();
            $config = $this->getBitcoinConfigurations();
            if ($type === Transaction::TRANSACTION_TYPE_DEPOSIT) {
                $rateSetting->setRangeFrom($config['minimumAllowedDeposit']);
                $rateSetting->setRangeTo($config['maximumAllowedDeposit']);
            } elseif ($type === Transaction::TRANSACTION_TYPE_WITHDRAW) {
                $rateSetting->setRangeFrom($config['minimumAllowedWithdrawal']);
                $rateSetting->setRangeTo($config['maximumAllowedWithdrawal']);
            }
            $rateSetting->setType($type);
            $rateSettings[] = $rateSetting;
        }

        foreach ($rateSettings as $setting) {
            $this->setLastTouchedDetails($setting);
        }

        return $rateSettings;
    }

    public function getNonDefaultWithdrawalRateSettings(): array
    {
        return $this->getNonDefaultRateSettings(Transaction::TRANSACTION_TYPE_WITHDRAW);
    }

    protected function setLastTouchedDetails(BitcoinRAteSetting $setting): void
    {
        if ($setting->getUpdatedBy()) {
            $user = $this->userRepository->findOneById($setting->getUpdatedBy());
            $setting->setLastTouchedBy($user);
        } else {
            $user = $this->userRepository->findOneById($setting->getCreatedBy());
            $setting->setLastTouchedBy($user);
        }
    }

    public function createRateSettingsDTO(): BitcoinRateSettingsDTO
    {
        $defaultRateSetting = $this->getDefaultRateSetting();
        $dto = new BitcoinRateSettingsDTO($defaultRateSetting);
        $rateSettings = $this->getNonDefaultRateSettings();
        $dto->setBitcoinRateSettings($rateSettings);

        return $dto;
    }

    public function createWithdrawalRateSettingsDTO(): BitcoinRateSettingsDTO
    {
        $defaultRateSetting = $this->getDefaultRateSetting(Transaction::TRANSACTION_TYPE_WITHDRAW);
        $dto = new BitcoinRateSettingsDTO($defaultRateSetting);
        $withdrawalRateSettings = $this->getNonDefaultWithdrawalRateSettings();
        $dto->setBitcoinRateSettings($withdrawalRateSettings);

        return $dto;
    }

    public function prepareRateSettingForm(Form $form): void
    {
        $dto = $this->createRateSettingsDTO();

        if (!$form->isSubmitted()) {
            $form->setData($dto);
        }
    }

    public function prepareWithdrawalRateSettingForm(Form $form): void
    {
        $dto = $this->createWithdrawalRateSettingsDTO();

        if (!$form->isSubmitted()) {
            $form->setData($dto);
        }
    }

    public function saveRateSettings(BitcoinRateSettingsDTO $dto, int $transactionType): void
    {
        $this->em->persist($dto->getDefaultRateSetting());

        foreach ($dto->getBitcoinRateSettings() as $setting) {
            if ($transactionType == Transaction::TRANSACTION_TYPE_DEPOSIT) {
                $setting->setDefaultType();
            } else {
                $setting->setWithdrawalType();
            }
            $this->em->persist($setting);
        }

        $removedItems = CollectionUtils::getRemovedItems($dto, 'bitcoinRateSettings');
        foreach ($removedItems as $item) {
            $this->em->remove($item);
        }
        
        $bitcoinAdjustment = $this->createBitcoinAdjustment(Rate::RATE_EUR, $transactionType);

        $this->dispatcher->dispatch(BitcoinRateSettingSaveEvent::NAME, new BitcoinRateSettingSaveEvent($bitcoinAdjustment, $transactionType));

        $this->em->flush();
    }

    private function getBitcoinAdjustmentComponent(): BitcoinAdjustmentInterface
    {
        return $this->bitcoinAdjustmentComponent;
    }

    public function setBitcoinAdjustmentComponent(BitcoinAdjustmentInterface $bitcoinAdjustmentComponent): void
    {
        $this->bitcoinAdjustmentComponent = $bitcoinAdjustmentComponent;
    }

    private function getBlockchain(): BlockChainInterface
    {
        return $this->blockchain;
    }

    public function setBlockchain(BlockChainInterface $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    public function findUserUnacknowledgedDepositBitcoinTransaction($customer): ?Transaction
    {
        $transaction = $this->transactionRepository->findUserUnacknowledgedDepositBitcoinTransaction($customer);
    
        if ($transaction === null) {
            return null;
        }

        return $transaction;
    }

    public function getBitcoinTransactionTimeRemaining(Transaction $transaction): string
    {
        $bitcoinSettings = $this->getBitcoinSettings();
        $interval = $bitcoinSettings['setting']['configuration']['minutesInterval'];

        if ($transaction->getUpdatedAt() === null) {
            return '';
        }

        $updatedAt = \DateTimeImmutable::createFromMutable($transaction->getUpdatedAt());
        $expiresAt = $updatedAt->add(new \DateInterval('PT' . $interval . 'M'));
        $now = new \DateTimeImmutable();

        if ($now < $expiresAt) {
            $remainingInterval = $expiresAt->diff($now);
            $remaining = $remainingInterval->format('%H:%I:%S');

            return $remaining;
        } else {
            return '00:00:00';
        }
    }

    public function getBitcoinTransactionLockdownRateRemaining(Transaction $transaction): string
    {
        $interval = $this->getBitcoinLockDownRateSetting()['minutesLockDownInterval'];
        
        if ($transaction->getUpdatedAt() === null) {
            return '';
        }

        $updatedAt = \DateTimeImmutable::createFromMutable($transaction->getUpdatedAt());
        $expiresAt = $updatedAt->add(new \DateInterval('PT' . $interval . 'M'));
        $now = new \DateTimeImmutable();

        if ($now < $expiresAt) {
            $remainingInterval = $expiresAt->diff($now);
            $remaining = $remainingInterval->format('%H:%I:%S');

            return $remaining;
        } else {
            return '00:00:00';
        }

        return '00:00:00';
    }

    public function decline(Transaction $transaction): void
    {
        if (!$this->getBitcoinAutoDeclineStatus()) {
            return;
        }

        if ($transaction->isDeclined() || $transaction->isBitcoinStatusConfirmed() || $transaction->isBitcoinStatusPendingConfirmation() || $transaction->isVoided() || $transaction->isEnd()) {
            return;
        }

        $timeRemaining = $this->getBitcoinTransactionTimeRemaining($transaction);
        if ($timeRemaining !== '00:00:00') {
            throw new \DomainException('Cannot decline bitcoin transaction. Transaction not yet expired.');
        }

        $transaction->setReasonToVoidOrDecline('No deposit received in payment gateway');
        $transaction->decline();

        $event = new TransactionProcessEvent($transaction);
        $this->dispatcher->dispatch('transaction.autoDeclined', $event);

        $this->em->flush();
    }
}
