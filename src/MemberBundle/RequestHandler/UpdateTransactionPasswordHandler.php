<?php

namespace MemberBundle\RequestHandler;

use DbBundle\Entity\Customer;
use DbBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use MemberBundle\Request\UpdateTransactionPasswordRequest;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class UpdateTransactionPasswordHandler
{
    private $entityManager;
    private $passwordEncoder;

    public function __construct(UserPasswordEncoder $passwordEncoder, EntityManager $entityManager)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->entityManager = $entityManager;
    }

    public function handleTransactionPassword(UpdateTransactionPasswordRequest $request): Customer
    {
        $customer = $request->getCustomer();
        $customer->setTransactionPassword($this->encodePassword($customer->getUser(), $request->getPassword()));

        $this->entityManager->persist($customer);
        $this->entityManager->flush($customer);

        return $customer;
    }

    private function encodePassword(User $user, $password): string
    {
        return $this->passwordEncoder->encodePassword($user, $password);
    }
}
