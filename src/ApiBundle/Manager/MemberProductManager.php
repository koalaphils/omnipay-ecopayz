<?php

namespace ApiBundle\Manager;

use ApiBundle\RequestHandler\CreateMemberProductRequestHandler;
use ApiBundle\Request\CreateMemberProductRequest\MemberProductList as CreateMemberProductRequest;
use DbBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MemberProductManager
{
    private $createMemberProductRequestHandler;
    private $tokenStorage;

    public function __construct(CreateMemberProductRequestHandler $createMemberProductRequestHandler, TokenStorageInterface $tokenStorage)
    {
        $this->createMemberProductRequestHandler = $createMemberProductRequestHandler;
        $this->tokenStorage = $tokenStorage;
    }

    public function create(CreateMemberProductRequest $createMemberProductRequest): ArrayCollection
    {
        return $this->getCreateMemberProductRequestHandler()->handle(
            $createMemberProductRequest,
            $this->getUser()->getMember()
        );
    }

    private function getUser(): User
    {
        return $this->tokenStorage->getToken()->getUser();
    }

    private function getCreateMemberProductRequestHandler(): CreateMemberProductRequestHandler
    {
        return $this->createMemberProductRequestHandler;
    }
}