<?php

declare(strict_types = 1);

namespace ApiBundle\RequestHandler;

use DbBundle\Entity\Customer;
use DbBundle\Repository\CustomerPaymentOptionRepository;
use PinnacleBundle\Service\PinnacleService;

class MemberHandler
{
    /**
     * @var PinnacleService
     */
    private $pinnacleService;

    /**
     * @var CustomerPaymentOptionRepository
     */
    private $memberPaymentOptionRepository;

    public function __construct(PinnacleService $pinnacleService, CustomerPaymentOptionRepository $memberPaymentOptionRepository)
    {
        $this->pinnacleService = $pinnacleService;
        $this->memberPaymentOptionRepository = $memberPaymentOptionRepository;
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

    public function handleGetActivePaymentOptionGroupByType(Customer $member): array
    {
        $paymentOptions = $this->memberPaymentOptionRepository->findActivePaymentOptionForMember((int) $member->getId());
        $groupPaymentOptions = [];
        foreach ($paymentOptions as $paymentOption) {
            $type = $paymentOption->getPaymentOption()->getCode();
            if (!array_has($groupPaymentOptions, $type)) {
                $groupPaymentOptions[$type] = [];
            }

            $groupPaymentOptions[$type][] = $paymentOption;
        }

        return $groupPaymentOptions;
    }
}