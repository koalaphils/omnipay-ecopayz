<?php
/**
 * Created by PhpStorm.
 * User: cydrick
 * Date: 3/26/19
 * Time: 1:27 PM
 */

namespace ApiBundle\RequestHandler;

use ApiBundle\Manager\CustomerManager;
use AppBundle\Helper\Publisher;
use DbBundle\Entity\OAuth2\AccessToken;
use DbBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OAuth2\OAuth2;
use OAuth2\OAuth2AuthenticateException;
use OAuth2\OAuth2ServerException;
use PinnacleBundle\Service\PinnacleService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

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

    public function __construct(
        OAuth2 $oauthService,
        PinnacleService $pinnacleService,
        EntityManagerInterface $entityManager,
        Publisher $publisher,
        CustomerManager $customerManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->oauthService = $oauthService;
        $this->pinnacleService = $pinnacleService;
        $this->entityManager = $entityManager;
        $this->publisher = $publisher;
        $this->customerManager = $customerManager;
        $this->tokenStorage = $tokenStorage;
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
        $response = $this->oauthService->grantAccessToken($request);

        $data = json_decode($response->getContent(), true);
        $accessToken = $this->oauthService->verifyAccessToken($data['access_token']);

        /* @var $user \DbBundle\Entity\User */
        $user = $accessToken->getUser();
        $pinLoginResponse = $this->pinnacleService->getAuthComponent()->login($user->getCustomer()->getPinUserCode());

        $loginResponse = $data;
        $loginResponse['pinnacle'] = $pinLoginResponse->toArray();
        $this->deleteUserAccessToken($accessToken->getUser()->getId(), [], [$accessToken->getToken()]);

        $this->loginUser($user);
        $this->customerManager->handleAudit('login');

        $channel = $user->getCustomer()->getWebsocketDetails()['channel_id'];
        $this->publisher->publish($channel . '.login', json_encode(['access_token' => $accessToken->getToken()]));

        return $loginResponse;
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
    }

    private function loginUser(User $user): void
    {
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }
}