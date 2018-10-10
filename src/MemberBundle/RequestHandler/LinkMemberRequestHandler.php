<?php

namespace MemberBundle\RequestHandler;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use MemberBundle\Event\ReferralEvent;
use MemberBundle\Events;

class LinkMemberRequestHandler
{
    private $doctrine;
    private $dispatcher;

    public function __construct(\Doctrine\Bundle\DoctrineBundle\Registry $doctrine,  EventDispatcherInterface $dispatcher)
    {
        $this->doctrine = $doctrine;
        $this->dispatcher = $dispatcher;
    }

    public function handle(\MemberBundle\Request\LinkMemberRequest $request)
    {
        foreach ($request->getReferrals() as $referral) {
            $referral = $this->getCustomerRepository()->find($referral);
            $referrer = $request->getMember();
            $referral->setReferrer($referrer);

            $this->dispatcher->dispatch(Events::EVENT_REFERRAL_LINKED, new ReferralEvent($referrer, $referral));

            $this->doctrine->getManager()->persist($referral);
            $this->doctrine->getManager()->flush();
        }
    }

    private function getCustomerRepository(): \DbBundle\Repository\CustomerRepository
    {
        return $this->doctrine->getRepository(\DbBundle\Entity\Customer::class);
    }
}
