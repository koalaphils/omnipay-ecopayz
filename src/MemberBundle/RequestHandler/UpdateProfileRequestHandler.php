<?php

namespace MemberBundle\RequestHandler;


use MemberBundle\Manager\MemberManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use AppBundle\Event\GenericEntityEvent;
use AuditBundle\Manager\AuditManager;
use DbBundle\Entity\AuditRevisionLog;
use DbBundle\Entity\Country;
use DbBundle\Entity\Currency;
use DbBundle\Entity\Customer;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\CustomerGroup;
use DbBundle\Entity\MarketingTool;
use DbBundle\Listener\VersionableListener;
use Doctrine\ORM\EntityManager;
use MemberBundle\Event\ReferralEvent;
use MemberBundle\Events;
use MemberBundle\Request\UpdateProfileRequest;

class UpdateProfileRequestHandler
{
    private $entityManager;
    private $requestStack;
    private $eventDisptacher;
    private $auditManager;
    private $memberManager;

    public function __construct(
        EntityManager $entityManager,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher,
        AuditManager $auditManager,
        MemberManager $memberManager
    )
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->eventDisptacher = $eventDispatcher;
        $this->auditManager = $auditManager;
        $this->memberManager = $memberManager;
    }

    public function handle(UpdateProfileRequest $request)
    {
        $customer = $request->getCustomer();
        $originalAffiliateLink = $customer->getUser()->getPreference('affiliateCode');
        $originalPromoCode = $customer->getUser()->getPreference('promoCode');

        $customer->getUser()->setUsername($request->getUsername());
        $customer->getUser()->setEmail($request->getEmail());
        $customer->getUser()->setPhoneNumber($request->getPhoneNumber());
        $customer->getUser()->setIsActive(empty($request->getStatus()) ? false : true);

        $customer->setFullName($request->getFullName());
        $customer->setFName($request->getFullName());
        if ($request->getCountry() !== null) {
            $customer->setCountry($this->entityManager->getPartialReference(Country::class, $request->getCountry()));
        }
        $customer->setCurrency($this->entityManager->getPartialReference(Currency::class, $request->getCurrency()));
        $customer->setBirthDate($request->getBirthDate());
        $customer->setGender($request->getGender());
        $customer->setJoinedAt($request->getJoinedAt());
        $customer->setRiskSetting($request->getRiskSetting());
        $customer->setTags($request->getTags());
        $customer->setLocale($request->getLocale());

        $this->setReferrerByCode($request->getAffiliateLink(), $customer);

        if ($this->hasChangeAffiliateLink($request->getAffiliateLink(), $originalAffiliateLink)) {
            $marketingTool = $this->entityManager->getRepository(MarketingTool::class)->findMarketingToolByMember($customer);
            if ($marketingTool instanceof MarketingTool) {
                $this->updateAffiliateLink($marketingTool, $customer, $originalAffiliateLink);
            } else {
                $marketingTool = new MarketingTool();
                $this->createNewAffiliateLink($marketingTool, $customer, $originalAffiliateLink);
            }

            $affiliateLinkLogDetails = [$originalAffiliateLink, $request->getAffiliateLink()];

            $this->logMarketingDetails($marketingTool, $affiliateLinkLogDetails, []);
        }

        if ($this->hasPreviousEmptyAffiliateLink($request->getAffiliateLink(), $originalAffiliateLink)) {
            $marketingTool = new MarketingTool();
            $marketingTool->setMember($customer);

            $affiliateLinkLogDetails = [$originalAffiliateLink, $request->getAffiliateLink()];

            $this->logMarketingDetails($marketingTool, $affiliateLinkLogDetails, []);
        }

        if ($this->hasChangePromoCode($request->getPromoCode(), $originalPromoCode)) {
            $marketingTool = new MarketingTool();
            $marketingTool->setMember($customer);

            $promoCodeLogDetails = [$customer->getUser()->getPreference('promoCode'), $request->getPromoCode()];

            $this->logMarketingDetails($marketingTool, [], $promoCodeLogDetails);
        }

        $customer->getUser()->setPreference('affiliateCode', $request->getAffiliateLink());
        $customer->getUser()->setPreference('promoCode', $request->getPromoCode());

        $memberGroups = [];
        foreach ($request->getGroups() as $groupId) {
            $memberGroups[] = $this->entityManager->getPartialReference(CustomerGroup::class, $groupId);
        }
        $customer->setGroups($memberGroups);

        $this->dispatchAffiliateLinkingEvent($customer, $request->getReferrer());
        if (!is_null($request->getReferrer())) {
            $customer->setAffiliate($this->entityManager->getPartialReference(Customer::class, $request->getReferrer()));
        } else {
            $customer->setAffiliate($request->getReferrer());
        }

        $this->entityManager->persist($customer->getUser());
        $this->entityManager->flush($customer->getUser());
        $this->entityManager->persist($customer);
        $this->entityManager->flush($customer);

        return $customer;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDisptacher = $eventDispatcher;
    }

    protected function dispatchEvent(string $eventName, Event $event): void
    {
        $this->getEventDispatcher()->dispatch($eventName, $event);
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDisptacher;
    }

    private function hasChangeAffiliateLink(?string $newAffiliateLink = '', ?string $originalAffiliateLink = ''): bool
    {
        if (($originalAffiliateLink != null) && ($originalAffiliateLink != $newAffiliateLink)) {
            return true;
        } else {
            return false;
        }
    }

    private function dispatchAffiliateLinkingEvent(Customer $member,  ?string $currentReferrerId): void
    {
        $oldReferrer = $member->getReferral();

        if ($oldReferrer === null && $currentReferrerId !== null) {
            $newReferrer = $this->entityManager->getRepository(Customer::class)->findById($currentReferrerId);
            $this->dispatchEvent(Events::EVENT_REFERRAL_LINKED, new ReferralEvent($newReferrer, $member));
        }
    }

    private function hasChangePromoCode(?string $newPromoCode = '', ?string $originalPromoCode = ''): bool
    {
        return $originalPromoCode != $newPromoCode;
    }

    private function hasPreviousEmptyAffiliateLink(?string $newAffiliateLink = '', ?string $originalAffiliateLink = ''): bool
    {
        return $originalAffiliateLink == null && $newAffiliateLink != null ? true : false;
    }

    private function updateAffiliateLink($marketingTool, $customer, $originalAffiliateLink): void
    {
        $marketingTool->preserveOriginal();
        $marketingTool->setMember($customer);
        $marketingTool->setAffiliateLink($originalAffiliateLink);

        $this->dispatchEvent(VersionableListener::VERSIONABLE_SAVE, new GenericEntityEvent($marketingTool));
    }

    private function createNewAffiliateLink(MarketingTool $marketingTool, Customer $customer, ?string $originalAffiliateLink = ''): void
    {
        $marketingTool->setMember($customer);
        $marketingTool->setAffiliateLink($originalAffiliateLink);
        $marketingTool->preserveOriginal();

        $this->dispatchEvent(VersionableListener::VERSIONABLE_SAVE, new GenericEntityEvent($marketingTool));
    }

    private function logMarketingDetails(MarketingTool $marketingTool, array $affiliateLinkLogDetails = [], array $promoCodeLogDetails = []): void
    {
        $auditLogFields = [
            'affiliateLink' => $affiliateLinkLogDetails,
            'promoCode' => $promoCodeLogDetails
        ];

        $this->auditManager->audit($marketingTool, AuditRevisionLog::OPERATION_UPDATE, null, $auditLogFields);
    }

    private function setReferrerByCode(?string $referrerCode, Member $member): void
    {
        if (!is_null($referrerCode) && $referrerCode !== '') {
            $referrer = $this->memberManager->getReferrerByReferrerCode($referrerCode);

            if (!is_null($referrer)) {
                $member->setReferrerByCode($referrer);
            }
        }
    }
}
