<?php

namespace AppBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter as BaseVoter;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use DbBundle\Entity\User;

class VoterDot extends BaseVoter
{
    /**
     * @var \Symfony\Component\Security\Core\Authorization\AccessDecisionManager
     */
    private $decisionManager;

    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

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

        /* $userRoles = array();
          foreach($user->getRoles() as $role) {
          array_set($userRoles, $role, 1);
          } */
        $userRoles = $user->getRoles();

        if (in_array('role.super.admin', $userRoles)) {
            return true;
        }

        $access = false;

        $attribute = str_replace('_', '.', strtolower($attribute));

        if (substr($attribute, -1) === '*') {
            $attrLen = strlen(substr($attribute, 0, -1));
            foreach ($userRoles as $role) {
                $rolSub = substr($role, 0, $attrLen);
                if ($rolSub === substr($attribute, 0, -1)) {
                    $access = true;
                    break;
                }
            }
        } else {
            $access = in_array($attribute, $userRoles);
        }

        return $access;
    }
}
