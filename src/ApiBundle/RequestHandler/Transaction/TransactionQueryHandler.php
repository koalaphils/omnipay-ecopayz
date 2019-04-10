<?php

declare(strict_types = 1);

namespace ApiBundle\RequestHandler\Transaction;

use ApiBundle\Request\Transaction\GetLastBitcoinRequest;
use AppBundle\Manager\SettingManager;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\TransactionRepository;
use Doctrine\ORM\NoResultException;

class TransactionQueryHandler
{
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * @var SettingManager
     */
    private $settingManager;

    public function __construct(TransactionRepository $transactionRepository, SettingManager $settingManager)
    {
        $this->transactionRepository = $transactionRepository;
        $this->settingManager = $settingManager;
    }

    public function handleGetLastBitcoin(GetLastBitcoinRequest $request): ?Transaction
    {
        $paymentOptionCode = $this->settingManager->getSetting('bitcoin.setting.paymentOption');
        try {
            return $this->transactionRepository->getLastTransactionForPaymentOption($request->getMemberId(), $paymentOptionCode);
        } catch (NoResultException $exception) {
            return null;
        }
    }
}