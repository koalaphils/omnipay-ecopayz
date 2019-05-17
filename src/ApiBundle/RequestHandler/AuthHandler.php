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
use ApiBundle\Request\ForgotPasswordRequest;
use AppBundle\Helper\Publisher;
use AppBundle\Manager\SettingManager;
use DbBundle\Entity\Customer;
use DbBundle\Entity\OAuth2\AccessToken;
use DbBundle\Entity\Session;
use DbBundle\Entity\User;
use DbBundle\Repository\PaymentOptionRepository;
use DbBundle\Repository\SessionRepository;
use DbBundle\Repository\UserRepository;
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

        /* @var $user \DbBundle\Entity\User */
        $user = $accessToken->getUser();
        $this->pinnacleService->getAuthComponent()->logout($user->getCustomer()->getPinUserCode());
        $pinLoginResponse = $this->pinnacleService->getAuthComponent()->login($user->getCustomer()->getPinUserCode());
        $paymentOptionTypes = $this->paymentOptionRepository->getMemberProcessedPaymentOption($user->getCustomer()->getId());
        $processPaymentOptionTypes = [];
        foreach ($paymentOptionTypes as $paymentOption) {
            $processPaymentOptionTypes[$paymentOption->getCode()] = true;
        }

        $loginResponse = [
            'token' => $data,
            'pinnacle' => $pinLoginResponse->toArray(),
            'member' => $user->getCustomer(),
            'process_payments' => $processPaymentOptionTypes,
            'jwt' => $this->generateJwtToken($user->getCustomer()),
        ];
        $this->deleteUserAccessToken($accessToken->getUser()->getId(), [], [$accessToken->getToken()]);

        if ($user->getCustomer()->getWebsocketChannel() === '') {
            $user->getCustomer()->setWebsocketChannel(uniqid(generate_code(10, false, 'ld')));
            $this->entityManager->persist($user->getCustomer());
            $this->entityManager->flush($user->getCustomer());
        }

        $this->saveSession($user, $data, $pinLoginResponse->toArray());

        $this->loginUser($user);
        $this->customerManager->handleAudit('login');

        $channel = $user->getCustomer()->getWebsocketDetails()['channel_id'];
        $this->publisher->publishUsingWamp('login.' . $channel, ['access_token' => $accessToken->getToken()]);

        return $loginResponse;
    }

    public function handleCheckSession(Request $request): array
    {
        $now = new \DateTimeImmutable('now');
        /* @var $user \DbBundle\Entity\User */
        $user = $this->tokenStorage->getToken()->getUser();
        $pinnacleToken = $request->get('pinnacle_token');
        $token = $this->tokenStorage->getToken();
        $session = $this->sessionRepository->findBySessionId($token->getToken());
        $updatePinnacleLogin = false;
        if ($session === null) {
            return ['success' => false];
        }
        $pinnacleInfo = $session->getDetail('pinnacle', ['token' => '']);

        if ($pinnacleInfo['token'] !== $pinnacleToken) {
            $pinnacleInfo = $this->pinnacleService->getAuthComponent()->login($user->getCustomer()->getPinUserCode())->toArray();
            $session->setDetail('pinnacle', $pinnacleInfo);
            $this->entityManager->persist($session);
            $this->entityManager->flush($session);
            $updatePinnacleLogin = true;
        }

        $updatedDate = \DateTimeImmutable::createFromFormat(\DateTimeImmutable::ISO8601, $pinnacleInfo['updated_date']);
        $expirationDate = $updatedDate->modify('+' . $this->pinnacleExpiration . ' seconds')->setTimezone($now->getTimezone());

        if ($expirationDate <= $now) {
            $pinnacleInfo = $this->pinnacleService->getAuthComponent()->login($user->getCustomer()->getPinUserCode())->toArray();
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

    public function handleRefreshToken(Request $request): array
    {
        $response = $this->oauthService->grantAccessToken($request);
        $data = json_decode($response->getContent(), true);
        $accessToken = $this->oauthService->verifyAccessToken($data['access_token']);
        $user = $accessToken->getUser();
        $pinLoginResponse = $this->pinnacleService->getAuthComponent()->login($user->getCustomer()->getPinUserCode());

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

            $this->pinnacleService->getAuthComponent()->logout($token->getUser()->getCustomer()->getPinUserCode());
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
            $user = $this->userRepository->findByEmail($forgotPasswordRequest->getEmail(), 1);
        }

        $user->setPassword($this->userManager->encodePassword($user, $forgotPasswordRequest->getPassword()));
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

    private function saveSession(User $user, array $accessToken, array $pinnacleInfo)
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