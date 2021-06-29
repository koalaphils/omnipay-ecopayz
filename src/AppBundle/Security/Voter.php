<?php

namespace AppBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter as BaseVoter;
use DbBundle\Entity\User;

class Voter extends BaseVoter
{
    protected function supports($attribute, $subject)
    {
        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $userRoles = $user->getRoles();
        $access = false;

        if (in_array('ROLE_SUPER_ADMIN', $userRoles)) {
            return true;
        }

        return in_array($attribute, $userRoles);
    }
}
