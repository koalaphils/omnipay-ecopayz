<?php

namespace MemberBundle\RequestHandler;

use DbBundle\Entity\CustomerPaymentOption as MemberPaymentOption;
use MemberBundle\Request\CreatePaymentOptionRequest;

class UpdatePaymentOptionHandler
{
    private $doctrine;

    public function __construct(\Doctrine\Bundle\DoctrineBundle\Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function handle(\MemberBundle\Request\UpdatePaymentOptionRequest $request): MemberPaymentOption
    {
        $memberPaymentOption = $request->getMemberPaymentOption();
        $memberPaymentOption->setFields($request->getFields());
        $memberPaymentOption->setIsActive($request->isActive());

        $this->getEntityManager()->persist($memberPaymentOption);
        $this->getEntityManager()->flush($memberPaymentOption);

        return $memberPaymentOption;
    }

    private function getPaymentOptionRepository(): \DbBundle\Repository\PaymentOptionRepository
    {
        return $this->doctrine->getRepository(\DbBundle\Repository\PaymentOptionRepository::class);
    }

    private function getEntityManager(): \Doctrine\ORM\EntityManager
    {
        return $this->doctrine->getManager();
    }
}
