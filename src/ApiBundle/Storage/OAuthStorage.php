<?php

namespace ApiBundle\Storage;

use FOS\OAuthServerBundle\Model\ClientInterface;
use FOS\OAuthServerBundle\Storage\OAuthStorage as BaseOAuthStorage;
use Symfony\Component\HttpFoundation\RequestStack;

class OAuthStorage extends BaseOAuthStorage
{
    private $requestStack;

    public function setRequestStack(RequestStack $requestStack): self
    {
        $this->requestStack = $requestStack;

        return $this;
    }

    public function createAccessToken($tokenString, \OAuth2\Model\IOAuth2Client $client, $data, $expires, $scope = null)
    {
        if (!$client instanceof ClientInterface) {
            throw new \InvalidArgumentException('Client has to implement the ClientInterface');
        }

        $token = $this->accessTokenManager->createToken();
        $token->setToken($tokenString);
        $token->setClient($client);
        $token->setExpiresAt($expires);
        $token->setScope($scope);
        $token->setIpAddress($this->requestStack->getCurrentRequest()->getClientIp());

        if (null !== $data) {
            $token->setUser($data);
        }

        $this->accessTokenManager->updateToken($token);

        return $token;
    }
}
