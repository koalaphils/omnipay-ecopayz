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
        if ($paymentOptionCode === null) {
            throw new \Exception('Unable to proceed due to bitcoin payment option is not yet configured');
        }
        try {
            $transactionType = '';
            if ($request->getType() === 'deposit') {
                $transactionType = Transaction::TRANSACTION_TYPE_DEPOSIT;
            } elseif ($request->getType() === 'withdraw') {
                $transactionType = Transaction::TRANSACTION_TYPE_WITHDRAW;
            }

            if ($transactionType === '') {
                throw new \Exception(sprintf('Transaction %s is not supported for bitcoin transaction', $request->getType()));
            }

            return $this->transactionRepository->getLastTransactionForPaymentOption($request->getMemberId(), 'BITCOIN', $transactionType);
        } catch (NoResultException $exception) {
            return null;
        }
    }
}
