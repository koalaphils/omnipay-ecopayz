<?php

declare(strict_types = 1);

namespace ApiBundle\RequestHandler;

use AppBundle\Helper\Publisher;
use DbBundle\Entity\Customer;
use DbBundle\Repository\CustomerPaymentOptionRepository;
use DbBundle\Repository\SessionRepository;
use Doctrine\ORM\EntityManager;
use PinnacleBundle\Service\PinnacleService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

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

    /**
     * @var SessionRepository
     */
    private $sessionRepository;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    private $publisher;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(
        PinnacleService $pinnacleService,
        CustomerPaymentOptionRepository $memberPaymentOptionRepository,
        EntityManager $entityManager,
        SessionRepository $sessionRepository,
        TokenStorage $tokenStorage,
        Publisher $publisher
    ) {
        $this->pinnacleService = $pinnacleService;
        $this->memberPaymentOptionRepository = $memberPaymentOptionRepository;
        $this->entityManager = $entityManager;
        $this->sessionRepository = $sessionRepository;
        $this->tokenStorage = $tokenStorage;
        $this->publisher = $publisher;
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

    public function changeMemberLocale(Request $request, Customer $member, string $locale): array
    {
        $token = $this->tokenStorage->getToken()->getToken();
        $session = $this->sessionRepository->findBySessionId($token);
        $member->setLocale($locale);

        $this->entityManager->persist($member);
        $this->entityManager->flush($member);

        $memberLocale = $member->getLocale();
        $memberLocale = strtolower(str_replace('_', '-', $memberLocale));

        $this->pinnacleService->getAuthComponent()->logout($member->getPinUserCode());
        $pinLoginResponse = $this->pinnacleService->getAuthComponent()->login($member->getPinUserCode(), $memberLocale);
        $session->setDetail('pinnacle', $pinLoginResponse->toArray());

        $this->entityManager->persist($session);
        $this->entityManager->flush($session);

        $channel = $member->getWebsocketDetails()['channel_id'];
        $this->publisher->publishUsingWamp('pinnacle.update.' . $channel, ['login_url' => $pinLoginResponse->loginUrl()]);

        return ['success' => true, 'pinnacle' => $pinLoginResponse->toArray()];
    }
}