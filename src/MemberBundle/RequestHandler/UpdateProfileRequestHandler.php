<?php

namespace MemberBundle\RequestHandler;


use MemberBundle\Manager\MemberManager;
use Symfony\Component\HttpFoundation\RequestStack;

use AppBundle\Service\AffiliateService;
use AppBundle\Event\GenericEntityEvent;
use AuditBundle\Manager\AuditManager;
use DbBundle\Entity\AuditRevisionLog;
use DbBundle\Entity\Country;
use DbBundle\Entity\Currency;
use DbBundle\Entity\Customer;
use DbBundle\Repository\CustomerGroupRepository;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\CustomerGroup;
use DbBundle\Entity\MarketingTool;
use DbBundle\Entity\User;
use DbBundle\Repository\UserRepository;
use DbBundle\Listener\VersionableListener;
use Doctrine\ORM\EntityManager;
use MemberBundle\Event\ReferralEvent;
use MemberBundle\Events;
use MemberBundle\Request\UpdateProfileRequest;

class UpdateProfileRequestHandler
{
    private $entityManager;
    private $requestStack;
    private $auditManager;
    private $memberManager;
    private $affiliateService;

    public function __construct(
        EntityManager $entityManager,
        RequestStack $requestStack,
        AuditManager $auditManager,
        MemberManager $memberManager,
        AffiliateService $affiliateService
    )
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->auditManager = $auditManager;
        $this->memberManager = $memberManager;
        $this->affiliateService = $affiliateService;
    }

    public function handle(UpdateProfileRequest $request)
    {
        $customer = $request->getCustomer();
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
        $customer->setDetail('referral_code', $request->getReferralCode());
        dump($request);
        

        // $this->setReferrerByCode($request->getAffiliateLink(), $customer);

        // if ($this->hasChangeAffiliateLink($request->getAffiliateLink(), $originalAffiliateLink)) {
        //     $marketingTool = $this->entityManager->getRepository(MarketingTool::class)->findMarketingToolByMember($customer);
        //     if ($marketingTool instanceof MarketingTool) {
        //         $this->updateAffiliateLink($marketingTool, $customer, $originalAffiliateLink);
        //     } else {
        //         $marketingTool = new MarketingTool();
        //         $this->createNewAffiliateLink($marketingTool, $customer, $originalAffiliateLink);
        //     }

        //     $affiliateLinkLogDetails = [$originalAffiliateLink, $request->getAffiliateLink()];

        //     $this->logMarketingDetails($marketingTool, $affiliateLinkLogDetails, []);
        // }

        // if ($this->hasPreviousEmptyAffiliateLink($request->getAffiliateLink(), $originalAffiliateLink)) {
        //     $marketingTool = new MarketingTool();
        //     $marketingTool->setMember($customer);

        //     $affiliateLinkLogDetails = [$originalAffiliateLink, $request->getAffiliateLink()];

        //     $this->logMarketingDetails($marketingTool, $affiliateLinkLogDetails, []);
        // }

        // if ($this->hasChangePromoCode($request->getPromoCode(), $originalPromoCode)) {
        //     $marketingTool = new MarketingTool();
        //     $marketingTool->setMember($customer);

        //     $promoCodeLogDetails = [$customer->getUser()->getPreference('promoCode'), $request->getPromoCode()];

        //     $this->logMarketingDetails($marketingTool, [], $promoCodeLogDetails);
        // }

        // $customer->getUser()->setPreference('affiliateCode', $request->getAffiliateLink());
        // $customer->getUser()->setPreference('promoCode', $request->getPromoCode());

        $memberGroups = [];
        foreach ($request->getGroups() as $groupId) {
            $memberGroups[] = $this->getCustomerGroupRepository()->findOneById($groupId);
        }
        $customer->setGroups($memberGroups);
        $this->processAffiliate($request, $customer);

        // $this->dispatchAffiliateLinkingEvent($customer, $request->getReferrer());
        // if (!is_null($request->getReferrer())) {
        //     $customer->setAffiliate($this->entityManager->getPartialReference(Customer::class, $request->getReferrer()));
        // } else {
        //     $customer->setAffiliate($request->getReferrer());
        // }

        $this->entityManager->persist($customer->getUser());
        $this->entityManager->flush($customer->getUser());
        $this->entityManager->persist($customer);
        $this->entityManager->flush($customer);

        return $customer;
    }

    private function processAffiliate($request, $customer)
    {
        $originalAffiliate = $customer->getAffiliate() ? $customer->getAffiliate() : null;
        $newAffiliate = +$request->getReferrer();
        // Do remapping if there is a change in affiliate
        if (+$originalAffiliate !== $newAffiliate) {
            if ($newAffiliate !== null) {
                $this->affiliateService->addMember(
                    $customer->getUser()->getId(),
                    $newAffiliate
                );

                $customer->setAffiliate($newAffiliate);
            }

            if ($request->getReferrer() === null) {
                $customer->setAffiliate(null);
            }


            if ($originalAffiliate !== null) {
                $this->affiliateService->removeMember(
                    $customer->getUser()->getId(),
                    $originalAffiliate
                );
            }
        }
    }
    private function getCustomerGroupRepository(): CustomerGroupRepository
    {
        return $this->entityManager->getRepository(CustomerGroup::class);
    }

    private function getUserRepository(): UserRepository
    {
        return $this->entityManager->getRepository(User::class);
    }
}
