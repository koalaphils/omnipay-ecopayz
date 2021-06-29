<?php


namespace ApiBundle\Security;


use Doctrine\Common\Persistence\ObjectManager;
use FOS\OAuthServerBundle\Entity\TokenManager;
use FOS\OAuthServerBundle\Model\AccessTokenManagerInterface;

class OAuthTokenManager extends TokenManager implements AccessTokenManagerInterface
{
    public function findTokenBy(array $criteria)
    {
        $value = null;
        $this->em->transactional(function() use($criteria, &$value){
            $value = parent::findTokenBy($criteria);
        });
        return $value;
    }
    public function findTokenByToken($token)
    {
        $value = null;
        $this->em->transactional(function() use($token, &$value){
            $value = parent::findTokenByToken($token);
        });
        return $value;
    }
}