<?php

namespace DbBundle\Serializer;

use JMS\Serializer\EventDispatcher\ObjectEvent;
use DbBundle\Repository\DWLRepository;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Context;
use AppBundle\Manager\SettingManager;
use PaymentBundle\Manager\BitcoinManager;
use TransactionBundle\Manager\TransactionManager;

class TransactionSerializerSubscriber implements \JMS\Serializer\EventDispatcher\EventSubscriberInterface
{
    private const TRANSACTION_COMMISSION_PERIOD = 'commission_period';

    protected $settingManager;
    protected $entityManager;
    protected $bitcoinManager;
    private $transactionManager;

    public function __construct(
        SettingManager $settingManager,
        EntityManager $entityManager,
        BitcoinManager $bitcoinManager
    ) {
        $this->settingManager = $settingManager;
        $this->entityManager = $entityManager;
        $this->bitcoinManager = $bitcoinManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerializeMethod'],
        ];
    }

    public function onPostSerializeMethod(ObjectEvent $event)
    {
        $object = $event->getObject();
        if (!($object instanceof \DbBundle\Entity\Transaction)) {
            return;
        }
        /* @var $visitor \JMS\Serializer\JsonSerializationVisitor */
        $visitor = $event->getVisitor();
        $status = $this->settingManager->getSetting('transaction.status.' . $object->getStatus());
        $visitor->setData('status', ['id' => $object->getStatus(), 'label' => $status['label']]);

        $context = $event->getContext();
        $groups = $this->getGroupsFor($context->attributes->get('groups')->get(), $context);
        
        if ($object->isTransactionPaymentBitcoin()) {
            $timeRemaining = $this->bitcoinManager->getBitcoinTransactionTimeRemaining($object);
            $lockDownRateTimeRemaining = $this->bitcoinManager->getBitcoinTransactionLockdownRateRemaining($object);
            $visitor->setData('time_remaining', $timeRemaining);
            $parsed = date_parse($timeRemaining);
            $seconds = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];

            $visitor->setData('time_remaining_seconds', $seconds);
            $visitor->setData('lock_down_rate_time_remaining', $lockDownRateTimeRemaining);
        }

        if (in_array('API', $groups) && ($object->isCommission() || $object->isRevenueShare())) {
            $commissionPeriod = $this->getTransactionManager()->getCommissionPeriodForTransaction($object->getId());
            $visitor->setData(self::TRANSACTION_COMMISSION_PERIOD, $commissionPeriod->getPeriodDateDetails());
        }
    }

    public function setTransactionManager(TransactionManager $transactionManager): void
    {
        $this->transactionManager = $transactionManager;
    }

    private function getTransactionManager(): TransactionManager
    {
        return $this->transactionManager;
    }

    private function getDWLRepository(): DWLRepository
    {
        return $this->entityManager->getRepository(\DbBundle\Entity\DWL::class);
    }

    private function getGroupsFor($groups, Context $navigatorContext)
    {
        $paths = $navigatorContext->getCurrentPath();
        foreach ($paths as $index => $path) {
            if (!array_key_exists($path, $groups)) {
                if ($index > 0) {
                    $groups = array('Default');
                }

                break;
            }

            $groups = $groups[$path];
        }

        return $groups;
    }
}
