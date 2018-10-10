<?php

namespace AppBundle\Interfaces;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

interface UserAwareInterface
{
    public function setTokenStorage(TokenStorageInterface $token);
}
