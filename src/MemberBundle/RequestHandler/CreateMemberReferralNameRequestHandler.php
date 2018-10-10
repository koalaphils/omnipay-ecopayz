<?php

namespace MemberBundle\RequestHandler;

use DbBundle\Entity\MemberReferralName;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use MemberBundle\Request\CreateMemberReferralNameRequest;

class CreateMemberReferralNameRequestHandler
{
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function handle(CreateMemberReferralNameRequest $request): MemberReferralName
    {
        $memberReferralName = new MemberReferralName();
        $memberReferralName->setName($request->getName());
        $memberReferralName->setMember($request->getMember());

        $this->getEntityManager()->persist($memberReferralName);
        $this->getEntityManager()->flush($memberReferralName);

        return $memberReferralName;
    }

    private function getEntityManager(): EntityManager
    {
        return $this->doctrine->getManager();
    }
}
