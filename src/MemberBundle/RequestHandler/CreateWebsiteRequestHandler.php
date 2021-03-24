<?php

namespace MemberBundle\RequestHandler;

use DbBundle\Entity\MemberWebsite;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use MemberBundle\Request\CreateWebsiteRequest;

class CreateWebsiteRequestHandler
{
    private $doctrine;


    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function handle(CreateWebsiteRequest $request): MemberWebsite
    {
        $website = MemberWebsite::create($request->getMember());
        $website->setWebsite($request->getWebsite());
        $this->getEntityManager()->persist($website);
        $this->getEntityManager()->flush($website);

        return $website;
    }

    private function getEntityManager(): EntityManager
    {
        return $this->doctrine->getManager();
    }
}
