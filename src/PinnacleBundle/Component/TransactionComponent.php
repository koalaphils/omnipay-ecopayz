<?php

declare(strict_types = 1);

namespace PinnacleBundle\Component;

use PinnacleBundle\Component\Model\DepositResponse;
use PinnacleBundle\Component\Model\WithdrawResponse;

class TransactionComponent extends PinnacleComponent
{
    private const DEPOSIT_PATH = '/player/deposit';
    private const WITHDRAW_PATH = '/player/withdraw';

    public function deposit(string $userCode, string $amount): DepositResponse
    {
        $data = $this->get(self::DEPOSIT_PATH, ['userCode' => $userCode, 'amount' => $amount]);

        return DepositResponse::create($data);
    }

    public function withdraw(string $userCode, string $amount): WithdrawResponse
    {
        $data = $this->get(self::WITHDRAW_PATH, ['userCode' => $userCode, 'amount' => $amount]);

        return WithdrawResponse::create($data);
    }
}
