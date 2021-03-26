<?php

namespace MemberBundle\RequestHandler;

use DbBundle\Entity\Customer;
use DbBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use MemberBundle\Request\UpdatePasswordRequest;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class UpdatePasswordRequestHandler
{
    private $entityManager;
    private $passwordEncoder;

    public function __construct(UserPasswordEncoder $passwordEncoder, EntityManager $entityManager)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->entityManager = $entityManager;
    }

    public function handlePassword(UpdatePasswordRequest $request): Customer
    {
        $user = $request->getCustomer()->getUser();
        $user->setPassword($this->encodePassword($user, $request->getPassword()));

        $this->entityManager->persist($user);
        $this->entityManager->flush($user);

        return $request->getCustomer();
    }

    private function encodePassword(User $user, $password): string
    {
        return $this->passwordEncoder->encodePassword($user, $password);
    }
}
