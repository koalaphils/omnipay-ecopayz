<?php

declare(strict_types = 1);

namespace ApiBundle\RequestHandler;

use ApiBundle\Service\JWTGeneratorService;
use AppBundle\Helper\Publisher;
use AppBundle\ValueObject\Number;
use DbBundle\Entity\Customer;
use DbBundle\Entity\Product;
use DbBundle\Repository\CountryRepository;
use DbBundle\Repository\CustomerPaymentOptionRepository;
use DbBundle\Repository\SessionRepository;
use DbBundle\Collection\Collection;
use Doctrine\ORM\EntityManager;
use Exception;
use OAuth2\OAuth2AuthenticateException;
use PinnacleBundle\Service\PinnacleService;
use ProductIntegrationBundle\ProductIntegrationFactory;
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

    /**
     * @var CountryRepository
     */
    private $countryRepository;

    private $productFactory;

    private $jwtGeneratorService;

    private $authHandler;

    public function __construct(
        PinnacleService $pinnacleService,
        CustomerPaymentOptionRepository $memberPaymentOptionRepository,
        EntityManager $entityManager,
        SessionRepository $sessionRepository,
        TokenStorage $tokenStorage,
        Publisher $publisher,
        CountryRepository $countryRepository,
        ProductIntegrationFactory $productFactory,
        JWTGeneratorService $jwtGeneratorService,
        AuthHandler $authHandler
    ) {
        $this->pinnacleService = $pinnacleService;
        $this->memberPaymentOptionRepository = $memberPaymentOptionRepository;
        $this->entityManager = $entityManager;
        $this->sessionRepository = $sessionRepository;
        $this->tokenStorage = $tokenStorage;
        $this->publisher = $publisher;
        $this->countryRepository = $countryRepository;
        $this->productFactory = $productFactory;
        $this->jwtGeneratorService = $jwtGeneratorService;
        $this->authHandler = $authHandler;
    }

    public function handleGetBalance($member): array
    {
        if ($member instanceof Customer && !is_null($member)) {
            $userCode = $member->getPinUserCode();
            $customerProducts = $member->getProducts();

            $productBalance = [];
            $availableBalance = new Number(0);
            $pinAvailableBalance = new Number(0);
            $pinOutstandingBalance = new Number(0);

            try {
                $player = $this->pinnacleService->getPlayerComponent()->getPlayer($userCode);
                $pinAvailableBalance = $player->availableBalance();
                $pinOutstandingBalance = $player->outstanding();
            } catch (Exception $ex) {
                $pinAvailableBalance = '';
                $pinOutstandingBalance = '';
            }

            foreach ($customerProducts as $customerProduct) {
                $productCode = $customerProduct->getProduct()->getCode();
                $productUsername = $customerProduct->getUserName();
                $token = $this->jwtGeneratorService->generate([]);

                try {
                    $productBalance[$productCode] = $this->productFactory->getIntegration(strtolower($productCode))->getBalance($token, $productUsername);
                    $availableBalance = $availableBalance->plus($productBalance[$productCode]);
                } catch (Exception $exception) {
                    $productBalance[$productCode] = '';
                }
            }

            return [
                'balance' => $member->getBalance(),
                'available_balance' => $availableBalance,
                'pinnacle_balance' => $pinAvailableBalance,
                'product_balance' => $productBalance,
                'outstanding' => $pinOutstandingBalance,
                'is_verified' => $member->isVerified()
            ];
        } else {
            return [
                'balance' => '',
                'available_balance' => '',
                'pinnacle_balance' => '',
                'product_balance' => '',
                'outstanding' => '',
                'is_verified' => false
            ];
        }
    }

    public function handleGetActivePaymentOptionGroupByType(Customer $member, ?string $transactionType): array
    {
        $paymentOptions = $this->memberPaymentOptionRepository->findActivePaymentOptionForMember((int) $member->getId(), $transactionType);
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
        $pinnacleDetails = $this->pinnacleService->getAuthComponent()->login($member->getPinUserCode(), $memberLocale);
        $session->setDetail('pinnacle', $pinnacleDetails->toArray());

        $token = $this->jwtGeneratorService->generate([]);
        $evolutionDetails = $this->authHandler->loginToEvolution($token, $member, $locale, $request);
        $session->setDetail('evolution', $evolutionDetails);

        $this->entityManager->persist($session);
        $this->entityManager->flush($session);

        $channel = $member->getWebsocketDetails()['channel_id'];
        $this->publisher->publishUsingWamp('pinnacle.update.' . $channel, ['login_url' => $pinnacleDetails->loginUrl()]);

        return ['success' => true, 'pinnacle' => $pinnacleDetails->toArray(), 'evolution' => $evolutionDetails];
    }

    public function changeMemberCountry(Customer $member, string $countryCode): array
    {
        try {
            $member = $member->setCountry($countryCode);

            $this->entityManager->persist($member);
            $this->entityManager->flush($member);

            $response = ['error' => false, 'data' => $countryCode, 'status' => 200];

        } catch (OAuth2AuthenticateException $exception) {
            $response = ['error' => true, 'data' => $exception->getMessage(), 'status' => $exception->getCode()];
        }

        return $response;
    }

    public function changeMemberDefaultProduct(Customer $member, string $product): array
    {
        $member = $member->setDetail('default_product', $product);

        $this->entityManager->persist($member);
        $this->entityManager->flush($member);

        return ['error' => false, 'data' => $product, 'status' => 200];
    }
}