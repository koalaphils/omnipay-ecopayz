<?php

namespace ApiBundle\Subscriber;

use AppBundle\Helper\ReferralToolGenerator;
use DbBundle\Entity\MemberBanner;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;

class MemberBannerSubscriber implements EventSubscriberInterface
{
    private $referralToolGenerator;

    public function __construct(ReferralToolGenerator $referralToolGenerator)
    {
        $this->referralToolGenerator = $referralToolGenerator;
    }

    public function onMemberBannerPreSerialize(ObjectEvent $event): void
    {
        $referralToolGenerator = $this->referralToolGenerator;
        $memberBanner = $event->getObject();

        $memberBanner->setReferralLink(
            $referralToolGenerator->generateReferralLink(
                $memberBanner->getReferralLinkOptions()
            )
        );
        $memberBanner->setTrackingHtmlCode(
            $referralToolGenerator->generateTrackingHtmlCode(
                $memberBanner->getTrackingHtmlOptions()
            )
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            ['event' => 'serializer.pre_serialize', 'method' => 'onMemberBannerPreSerialize', 'class' => MemberBanner::class],
        ];
    }
}
