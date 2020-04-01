<?php
/**
 * Created by PhpStorm.
 * User: cydrick
 * Date: 3/26/19
 * Time: 1:27 PM
 */

namespace ApiBundle\RequestHandler;

use ApiBundle\Exceptions\NoSessionExistsException;
use ApiBundle\Manager\CustomerManager;
use ApiBundle\Request\Auth\CheckSessionRequest;
use ApiBundle\Request\ChangePasswordRequest;
use ApiBundle\Request\ForgotPasswordRequest;
use AppBundle\Helper\Publisher;
use AppBundle\Manager\SettingManager;
use DbBundle\Entity\Customer;
use DbBundle\Entity\OAuth2\AccessToken;
use DbBundle\Entity\Session;
use DbBundle\Entity\User;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\Product;
use DbBundle\Repository\SessionRepositoryRepository;
use DbBundle\Repository\PaymentOptionRepository;
use DbBundle\Repository\SessionRepository;
use DbBundle\Repository\UserRepository;
use DbBundle\Repository\CustomerProductRepository;
use DbBundle\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use OAuth2\OAuth2;
use OAuth2\OAuth2AuthenticateException;
use OAuth2\OAuth2ServerException;
use PinnacleBundle\Service\PinnacleService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use UserBundle\Manager\UserManager;
use ProductIntegrationBundle\ProductIntegrationFactory;
use ProductIntegrationBundle\Exception\IntegrationNotAvailableException;
use ProductIntegrationBundle\Exception\IntegrationException;
use ProductIntegrationBundle\Exception\NoPinnacleProductException;

class AuthHandler
{
    /**
     * @var OAuth2
     */
    private $oauthService;

    /**
     * @var PinnacleService
     */
    private $pinnacleService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var CustomerManager
     */
    private $customerManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var PaymentOptionRepository
     */
    private $paymentOptionRepository;

    /**
     * @var string
     */
    private $jwtKey;

    /**
     * @var string
     */
    private $sessionExpiration;

    /**
     * @var SettingManager
     */
    private $settingManager;

    /**
     * @var SessionRepository
     */
    private $sessionRepository;

    /**
     * @var string
     */
    private $pinnacleExpiration;
    private $customerProductRepository;
    private $productRepository;
    private $productIntegrationFactory;

    public function __construct(
        OAuth2 $oauthService,
        PinnacleService $pinnacleService,
        EntityManagerInterface $entityManager,
        Publisher $publisher,
        CustomerManager $customerManager,
        TokenStorageInterface $tokenStorage,
        UserRepository $userRepository,
        PaymentOptionRepository $paymentOptionRepository,
        UserManager $userManager,
        SettingManager $settingManager,
        SessionRepository $sessionRepository,
        CustomerProductRepository $customerProductRepository,
        ProductRepository $productRepository,
        ProductIntegrationFactory $productIntegrationFactory,
        string $jwtKey
    ) {
        $this->oauthService = $oauthService;
        $this->pinnacleService = $pinnacleService;
        $this->entityManager = $entityManager;
        $this->publisher = $publisher;
        $this->customerManager = $customerManager;
        $this->tokenStorage = $tokenStorage;
        $this->userRepository = $userRepository;
        $this->userManager = $userManager;
        $this->paymentOptionRepository = $paymentOptionRepository;
        $this->jwtKey = $jwtKey;
        $this->settingManager = $settingManager;
        $this->sessionExpiration = $this->settingManager->getSetting('session.timeout');
        $this->pinnacleExpiration = $this->settingManager->getSetting('session.pinnacle_timeout');
        $this->sessionRepository = $sessionRepository;
        $this->productIntegrationFactory = $productIntegrationFactory;
        $this->customerProductRepository = $customerProductRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * @param Request $request
     * @return array
     *
     * @throws OAuth2ServerException
     * @throws \OAuth2\OAuth2AuthenticateException
     */
    public function handleLogin(Request $request): array
    {
        $user = $this->userRepository->loadUserByUsernameAndType($request->get('username'), User::USER_TYPE_MEMBER);
        if ($user === null) {
            throw new UsernameNotFoundException('Account does not exist.');
        }

        $response = $this->oauthService->grantAccessToken($request);
        $data = json_decode($response->getContent(), true);
        $accessToken = $this->oauthService->verifyAccessToken($data['access_token']);
        $user = $accessToken->getUser();
        $member = $user->getCustomer();

        $this->loginUser($user);

        $jwt = $this->generateJwtToken($user->getCustomer());
        $integrationResponses = $this->loginToProducts($user, $jwt, $request);

        $loginResponse = array_merge([
            'token' => $data,
            'member' => $member,
            'process_payments' => $this->getPaymentOptions($user->getCustomer()->getId()),
            'products' => $member->getProducts(),
            'jwt' => $jwt,
        ], $integrationResponses);

        $this->deleteUserAccessToken($accessToken->getUser()->getId(), [], [$accessToken->getToken()]);

        if ($user->getCustomer()->getWebsocketChannel() === '') {
            $user->getCustomer()->setWebsocketChannel(uniqid(generate_code(10, false, 'ld')));
            $this->entityManager->persist($user->getCustomer());
            $this->entityManager->flush($user->getCustomer());
        }

        $this->saveSession($user, $data, $integrationResponses['pinnacle']);
        $this->customerManager->handleAudit('login');

        $channel = $user->getCustomer()->getWebsocketDetails()['channel_id'];
        $this->publisher->publishUsingWamp('login.' . $channel, ['access_token' => $accessToken->getToken()]);

        return $loginResponse;
    }

    private function loginToProducts(User $user, string $jwt, $request): array
    {
        $memberLocale = $user->getCustomer()->getLocale();
        $memberLocale = strtolower(str_replace('_', '-', $memberLocale));
        $locale = !empty($memberLocale) ? $memberLocale : 'en';

        $this->createPiwiWalletIfNotExisting($user->getCustomer());

        return [
            'pinnacle' => $this->loginToPinnacle($user->getCustomer()->getPinUserCode(), $locale),
            'evolution' => $this->loginToEvolution($jwt, $user->getCustomer(), $locale, $request)
        ];
    }

    private function createPiwiWalletIfNotExisting(Customer $customer): void 
    {
        $customerProduct = $this->customerProductRepository->findOneByCustomerAndProductCode($customer, Product::MEMBER_WALLET_CODE);

        if ($customerProduct === null) {
            $customerProduct = CustomerProduct::create($customer);
            $product = $this->productRepository->getProductByCode(Product::MEMBER_WALLET_CODE);
            $customerProduct->setProduct($product);
            $customerProduct->setUsername(Product::MEMBER_WALLET_CODE . '_' . uniqid());
            $customerProduct->setBalance('0.00');
            $customerProduct->setIsActive(true);
            $this->entityManager->persist($customerProduct);
            $this->entityManager->flush();
        }
    }

    private function loginToPinnacle(?string $pinUserCode, $locale): ?array
    {   
        try {
            if ($pinUserCode === null || empty($pinUserCode)) {
                return null;
            }

            return $this->productIntegrationFactory->getIntegration('pinbet')
                ->auth($pinUserCode, ['locale' => $locale]);
        } catch (IntegrationNotAvailableException $ex) {
            return null;
        } catch (IntegrationException $ex) {
            return null;
        } 
    }

    public function loginToEvolution(string $jwt, Customer $customer, string $locale, Request $request): ?array
    {
        try {
            $evolutionIntegration = $this->productIntegrationFactory->getIntegration('evolution');
            $evolutionProduct = $this->getEvolutionProduct($customer);
            $evolutionResponse = $evolutionIntegration->auth($jwt, [
                'id' => $evolutionProduct->getUsername(),
                'lastName' => $customer->getLName() ? $customer->getLname() : $customer->getUsername(),
                'firstName' => $customer->getFName() ? $customer->getFName() : $customer->getUsername(),
                'nickname' => $customer->getUsername(),
                'country' => $customer->getCountry() ? $customer->getCountry()->getCode() : 'UK',
                'language' => $locale ?? 'en',
                'currency' => $customer->getCurrency()->getCode(),
                'ip' => $request->getClientIp(),
                'sessionId' =>$request->get('session_id')
            ]);

            $this->entityManager->persist($evolutionProduct);
            $this->entityManager->flush();
            
            return $evolutionResponse;
        } catch (IntegrationNotAvailableException $ex) {
            return null;
        } catch (IntegrationException $ex) {
            return null;
        }
    }

    private function getEvolutionProduct(Customer $customer): CustomerProduct
    {
        $customerProduct = $this->customerProductRepository->findOneByCustomerAndProductCode($customer, 'EVOLUTION');

        if ($customerProduct === null) {
            $customerProduct = CustomerProduct::create($customer);
            $product = $this->productRepository->getProductByCode('EVOLUTION');
            $customerProduct->setProduct($product);
            $customerProduct->setUsername('Evolution_' . uniqid());
            $customerProduct->setBalance('0.00');
            $customerProduct->setIsActive(true);
        }

        return $customerProduct;
    }

    private function getPaymentOptions(string $customerId): array
    {
        $paymentOptionTypes = $this->paymentOptionRepository->getMemberProcessedPaymentOption($customerId);
 
        return array_reduce($paymentOptionTypes, function($carry, $paymentOption) {
            $carry[$paymentOption->getCode()] = true;

            return $carry;
        }, []);
    }

    private function generateJwtToken(Customer $member): string
    {
        $token = [
            'authid' => json_encode([
                'username' => $member->getUsername(),
                'userid' => $member->getUser()->getId(),
                'from' => 'member_site',
            ]),
            'exp' => time() + $this->sessionExpiration,
        ];

        return JWT::encode($token, $this->jwtKey);
    }

    public function handleCheckSession(Request $request): array
    {
        $now = new \DateTimeImmutable('now');
        /* @var $user \DbBundle\Entity\User */
        $user = $this->tokenStorage->getToken()->getUser();
        $memberLocale = $user->getCustomer()->getLocale();
        $memberLocale = strtolower(str_replace('_', '-', $memberLocale));

        $pinnacleToken = $request->get('pinnacle_token');
        $token = $this->tokenStorage->getToken();
        $session = $this->sessionRepository->findBySessionId($token->getToken());
        $updatePinnacleLogin = false;
        if ($session === null) {
            return ['success' => false];
        }
        
        $pinnacleInfo = $session->getDetail('pinnacle', ['token' => '']);
        $updatedDate = \DateTimeImmutable::createFromFormat(\DateTimeImmutable::ISO8601, $pinnacleInfo['updated_date']);
        $expirationDate = $updatedDate->modify('+' . $this->pinnacleExpiration . ' seconds')->setTimezone($now->getTimezone());

        if ($pinnacleInfo['token'] !== $pinnacleToken || $expirationDate <= $now) {
            $pinnacleInfo = $this->productIntegrationFactory->getIntegration('pinbet')
                ->auth($user->getCustomer()->getPinUserCode(), ['locale' => $memberLocale]);
            $session->setDetail('pinnacle', $pinnacleInfo);
            $this->entityManager->persist($session);
            $this->entityManager->flush($session);
            $updatePinnacleLogin = true;
        }

        if ($updatePinnacleLogin) {
            $channel = $user->getCustomer()->getWebsocketDetails()['channel_id'];
            $this->publisher->publishUsingWamp('pinnacle.update.' . $channel, ['login_url' => $pinnacleInfo['login_url']]);
        }

        return [
            'success' => true,
            'pinnacle_updated' => $updatePinnacleLogin,
            'pinnacle' => $pinnacleInfo
        ];
    }

    public function handleRefreshToken(Request $request): array
    {
        $response = $this->oauthService->grantAccessToken($request);
        $data = json_decode($response->getContent(), true);
        $accessToken = $this->oauthService->verifyAccessToken($data['access_token']);
        $user = $accessToken->getUser();
        $memberLocale = $user->getCustomer()->getLocale();
        $pinLoginResponse = $this->productIntegrationFactory->getIntegration('pinbet')
            ->auth($user->getCustomer()->getPinUserCode(), ['locale' => $memberLocale]);

        return [
            'token' => $data,
            'pinnacle' => $pinLoginResponse->toArray(),
            'member' => $user->getCustomer(),
            'jwt' => $this->generateJwtToken($user->getCustomer())
        ];
    }

    public function handleLogout(Request $request): void
    {
        $tokenString = $request->get('token');
        try {
            $token = $this->oauthService->verifyAccessToken($tokenString);

            if ($token->getUser()->getCustomer()->getPinUserCode()) {
                $this->pinnacleService->getAuthComponent()->logout($token->getUser()->getCustomer()->getPinUserCode());
            }   
           
            $this->deleteUserAccessToken(null, [$tokenString]);
            $this->loginUser($token->getUser());
            $this->customerManager->handleAudit('logout');
        } catch (OAuth2AuthenticateException $exception) {
            if ($exception->getDescription() === 'The access token provided has expired.') {
                $this->deleteUserAccessToken(null, [$tokenString]);
            }
        }
    }

    public function handleForgotPassword(ForgotPasswordRequest $forgotPasswordRequest): void
    {
        if ($forgotPasswordRequest->getEmail() === '') {
            $user = $this->userRepository->findUserByPhoneNumber($forgotPasswordRequest->getPhoneNumber(), $forgotPasswordRequest->getCountryPhoneCode());
        } else {
            $user = $this->userRepository->findByEmail($forgotPasswordRequest->getEmail(), User::USER_TYPE_MEMBER);
            if (is_null($user) || empty($user)) {
                $user = $this->userRepository->findByEmail($forgotPasswordRequest->getEmail(), User::USER_TYPE_AFFILIATE);
            }
        }

        $user->setPassword($this->userManager->encodePassword($user, $forgotPasswordRequest->getPassword()));
        $this->entityManager->persist($user);
        $this->entityManager->flush($user);
    }

    public function handleChangePassword(ChangePasswordRequest $changePasswordRequest): void
    {
        $user = $changePasswordRequest->getUser();

        $user->setPassword($this->userManager->encodePassword($user, $changePasswordRequest->getPassword()));
        $this->entityManager->persist($user);
        $this->entityManager->flush($user);
    }

    private function deleteUserAccessToken(?int $userId = null, array $accessTokens = [], array $except = []): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->delete(AccessToken::class, 'a');

        if ($userId !== null) {
            $queryBuilder->andWhere('a.user = :userId')->setParameter('userId', $userId);
        }

        if (!empty($accessTokens)) {
            $queryBuilder->andWhere('a.token IN (:accessTokens)')->setParameter('accessTokens', $accessTokens);
        }

        if (!empty($except)) {
            $queryBuilder->andWhere('a.token NOT IN (:except)')->setParameter('except', $except);
        }

        $queryBuilder->getQuery()->execute();

        if ($userId === null && !empty($accessTokens)) {
            $this->sessionRepository->deleteUserSessionBySessionIds($accessTokens);
        } else {
            $this->sessionRepository->deleteUserSessionExcept($userId, $except);
        }
    }

    private function loginUser(User $user): void
    {
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }

    private function saveSession(User $user, array $accessToken, ?array $pinnacleInfo)
    {
        $session = new Session();
        $session->setDetails([
            'token' => $accessToken,
            'pinnacle' => $pinnacleInfo,
        ]);
        $session->setUser($user);
        $session->setSessionId($accessToken['access_token']);
        $session->setKey(generate_code(16, false, 'luds'));

        $this->entityManager->persist($session);
        $this->entityManager->flush($session);
    }
}