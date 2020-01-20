<?php

declare(strict_types = 1);

namespace PinnacleBundle\Component;

use PinnacleBundle\Component\Model\WinlossResponse;

class ReportComponent extends PinnacleComponent
{
    private const WINLOSS_PATH = '/report/winloss-simple';

    public function winloss(string $userCode, string $dateFrom, string $dateTo): WinlossResponse
    {
        $data = $this->get(self::WINLOSS_PATH, ['userCode' => $userCode, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo]);

        return WinlossResponse::create($data);
    }
}