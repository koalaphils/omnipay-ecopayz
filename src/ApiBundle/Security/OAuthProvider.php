<?php

namespace ApiBundle\Security;

use FOS\OAuthServerBundle\Security\Authentication\Provider\OAuthProvider as BaseOAuthProvider;
use FOS\OAuthServerBundle\Security\Authentication\Token\OAuthToken;
use OAuth2\OAuth2;
use OAuth2\OAuth2AuthenticateException;
use OAuth2\OAuth2ServerException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class OAuthProvider implements AuthenticationProviderInterface
{
    /**
     * @var UserProviderInterface
     */
    protected $userProvider;
    /**
     * @var OAuth2
     */
    protected $serverService;
    /**
     * @var UserCheckerInterface
     */
    protected $userChecker;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param UserProviderInterface $userProvider  The user provider.
     * @param OAuth2                $serverService The OAuth2 server service.
     * @param UserCheckerInterface  $userChecker   The Symfony User Checker for Pre and Post auth checks
     */
    public function __construct(
        UserProviderInterface $userProvider,
        OAuth2 $serverService,
        UserCheckerInterface $userChecker,
        RequestStack $requestStack
    ) {
        $this->userProvider = $userProvider;
        $this->serverService = $serverService;
        $this->userChecker = $userChecker;
        $this->requestStack = $requestStack;
    }

    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            return;
        }

        try {
            $tokenString = $token->getToken();
            $accessToken = $this->verifyAccessToken($tokenString);

            if ($accessToken) {
                $scope = $accessToken->getScope();
                $user = $accessToken->getUser();

                if (null !== $user) {
                    try {
                        $this->userChecker->checkPreAuth($user);
                    } catch (AccountStatusException $e) {
                        throw new OAuth2AuthenticateException(
                            OAuth2::HTTP_UNAUTHORIZED,
                            OAuth2::TOKEN_TYPE_BEARER,
                            $this->serverService->getVariable(OAuth2::CONFIG_WWW_REALM),
                            'access_denied',
                            $e->getMessage()
                        );
                    }

                    $token->setUser($user);
                }

                $roles = (null !== $user) ? $user->getRoles() : array();

                if (!empty($scope)) {
                    foreach (explode(' ', $scope) as $role) {
                        $roles[] = 'ROLE_' . strtoupper($role);
                    }
                }

                $roles = array_unique($roles, SORT_REGULAR);

                $token = new OAuthToken($roles);
                $token->setAuthenticated(true);
                $token->setToken($tokenString);

                if (null !== $user) {
                    try {
                        $this->userChecker->checkPostAuth($user);
                    } catch (AccountStatusException $e) {
                        throw new OAuth2AuthenticateException(
                            OAuth2::HTTP_UNAUTHORIZED,
                            OAuth2::TOKEN_TYPE_BEARER,
                            $this->serverService->getVariable(OAuth2::CONFIG_WWW_REALM),
                            'access_denied',
                            $e->getMessage()
                        );
                    }

                    $token->setUser($user);
                }

                return $token;
            }
        } catch (OAuth2ServerException $e) {
            if (!method_exists('Symfony\Component\Security\Core\Exception\AuthenticationException', 'setToken')) {
                // Symfony 2.1
                throw new AuthenticationException('OAuth2 authentication failed', null, 0, $e);
            }

            throw new AuthenticationException('OAuth2 authentication failed', 0, $e);
        }

        throw new AuthenticationException('OAuth2 authentication failed');
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof OAuthToken;
    }


    private function verifyAccessToken(string $tokenString, $scope = null)
    {
        $accessToken = $this->serverService->verifyAccessToken($tokenString);
        $tokenType = $this->serverService->getVariable(OAuth2::CONFIG_TOKEN_TYPE);
        $realm = $this->serverService->getVariable(OAuth2::CONFIG_WWW_REALM);

        $ips = array_merge($this->requestStack->getCurrentRequest()->getClientIps(), [$this->requestStack->getCurrentRequest()->server->get('REMOTE_ADDR')]);
        if (!in_array($accessToken->getIpAddress(), $ips)) {
            throw new OAuth2AuthenticateException(OAuth2::HTTP_UNAUTHORIZED, $tokenType, $realm, OAuth2::ERROR_USER_DENIED, 'The access token came from other ip.', $scope);
        }

        return $accessToken;
    }
}