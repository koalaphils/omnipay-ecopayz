<?php

namespace AppBundle\Traits;

/**
 * @author cnonog
 */
trait UserAwareTrait
{
    protected function _getUser()
    {
        if (!$this->hasSecurityTokenStorage()) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        $token = $this->getSecurityTokenStorage()->getToken();
        if ($token === null) {
            return;
        }

        $user = $token->getUser();
        if (!is_object($user)) {
            return;
        }

        return $user;
    }

    abstract protected function getSecurityTokenStorage();

    abstract protected function hasSecurityTokenStorage();
}
