<?php

declare(strict_types = 1);

namespace ApiBundle\RequestHandler;

use DbBundle\Entity\Customer;
use PinnacleBundle\Service\PinnacleService;

class MemberHandler
{
    /**
     * @var PinnacleService
     */
    private $pinnacleService;

    public function __construct(PinnacleService $pinnacleService)
    {
        $this->pinnacleService = $pinnacleService;
    }

    public function handleGetBalance(Customer $member): array
    {
        $userCode = $member->getPinUserCode();
        $player = $this->pinnacleService->getPlayerComponent()->getPlayer($userCode);

        return [
            'available_balance' => $player->availableBalance(),
            'outstanding' => $player->outstanding(),
        ];
    }
}