<?php

namespace MemberBundle\RequestHandler;

use DbBundle\Entity\CustomerPaymentOption as MemberPaymentOption;
use DbBundle\Entity\PaymentOption;
use DbBundle\Repository\PaymentOptionRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use MemberBundle\Request\CreatePaymentOptionRequest;

class CreatePaymentOptionHandler
{
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function handle(CreatePaymentOptionRequest $request): MemberPaymentOption
    {
        $memberPaymentOption = new MemberPaymentOption();
        $memberPaymentOption->setMember($request->getMember());
        $memberPaymentOption->setFields($request->getFields());
        $memberPaymentOption->setIsActive($request->isActive());
        $paymentOption = $this->getPaymentOptionRepository()->find($request->getType());
        $memberPaymentOption->setPaymentOption($paymentOption);
        $memberPaymentOption->setType($request->getType());
        $memberPaymentOption->setForDeposit();
        $memberPaymentOption->setForWithdrawal();

        $this->getEntityManager()->persist($memberPaymentOption);
        $this->getEntityManager()->flush($memberPaymentOption);

        return $memberPaymentOption;
    }

    private function getPaymentOptionRepository(): PaymentOptionRepository
    {
        return $this->doctrine->getRepository(PaymentOption::class);
    }

    private function getEntityManager(): EntityManager
    {
        return $this->doctrine->getManager();
    }
}
