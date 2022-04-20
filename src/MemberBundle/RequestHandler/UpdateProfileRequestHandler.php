<?php

namespace MemberBundle\RequestHandler;

use AppBundle\Service\AffiliateService;
use AuditBundle\Manager\AuditManager;
use DbBundle\Entity\Customer;
use DbBundle\Entity\MemberTag;
use DbBundle\Repository\UserRepository;
use DbBundle\Entity\Currency;
use DbBundle\Repository\CustomerGroupRepository;
use DbBundle\Entity\CustomerGroup;
use DbBundle\Entity\User;
use MemberBundle\Event\KycVerificationLevelChangedEvent;
use MemberBundle\Events;
use MemberBundle\Manager\MemberManager;
use MemberBundle\Request\UpdateProfileRequest;
use PromoBundle\Manager\PromoManager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class UpdateProfileRequestHandler
{
    private $entityManager;
    private $requestStack;
    private $eventDispatcher;
    private $auditManager;
    private $memberManager;
    private $affiliateService;
    private $promoManager;

    public function __construct(
        EntityManager $entityManager,
        RequestStack $requestStack,
        EventDispatcherInterface $eventDispatcher,
        AuditManager $auditManager,
        MemberManager $memberManager,
        AffiliateService $affiliateService,
        PromoManager $promoManager
    )
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->eventDispatcher = $eventDispatcher;
        $this->auditManager = $auditManager;
        $this->memberManager = $memberManager;
        $this->affiliateService = $affiliateService;
        $this->promoManager = $promoManager;
    }

    public function handle(UpdateProfileRequest $request)
    {
        $customer = $request->getCustomer();

        $customer->getUser()->setUsername($request->getUsername());
        $customer->getUser()->setEmail($request->getEmail());
        $customer->getUser()->setPhoneNumber($request->getPhoneNumber());
        $customer->getUser()->setIsActive(empty($request->getStatus()) ? false : true);

        $customer->setFullName($request->getFullName());
        $customer->setFName($request->getFullName());
        if ($request->getCountry() !== null) {
            $customer->setCountry($request->getCountry());
        }
        $customer->setCurrency($this->entityManager->getPartialReference(Currency::class, $request->getCurrency()));
        $customer->setBirthDate($request->getBirthDate());
        $customer->setGender($request->getGender());
        $customer->setJoinedAt($request->getJoinedAt());
        $customer->setRiskSetting($request->getRiskSetting());
        $customer->setTags($request->getTags());
        $customer->setLocale($request->getLocale());
        $customer->setDetail('referral_code', $request->getReferralCode());
        $customer->setPromoCode('custom', $request->getPromoCodeCustom());
        $customer->setPromoCode('refer_a_friend', $request->getPromoCodeReferAFriend());
        
        foreach ($request->getGroups() as $groupId) {
            $group = $this->getCustomerGroupRepository()->findOneById($groupId);
            $customer->addGroup($group);
        }

        $this->processAffiliate($request, $customer);

        $memberTags = [];
        if ($request->getMemberTags()){
            foreach ($request->getMemberTags() as $memberTag) {
                $memberTags[] = $this->entityManager->getPartialReference(MemberTag::class, $memberTag);
            }
        }
        $originalTags = $customer->getMemberTags()->toArray();
        $customer->setMemberTags(new ArrayCollection($memberTags));
        
        $this->entityManager->persist($customer->getUser());
        $this->entityManager->flush($customer->getUser());
        $this->entityManager->persist($customer);
        $this->entityManager->flush($customer);

        $this->promoManager->createPersonalLinkId($customer);
        $this->checkKycLevelChanges($customer, $memberTags, $originalTags);

        return $customer;
    }

    private function processAffiliate($request, $customer)
    {
        $originalAffiliate = $customer->getAffiliate() ? $customer->getAffiliate() : null;
        $newAffiliate = $request->getReferrer();
        // Do remapping if there is a change in affiliate
        if (+$originalAffiliate !== $newAffiliate) {
            if ($originalAffiliate !== null) {
                $this->affiliateService->removeMember(
                    $customer->getUser()->getId(),
                    $originalAffiliate
                );
            }
            
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
        }
    }
    private function checkKycLevelChanges(Customer $member, $currentTags, $oldTags): void
    {
        if (count($currentTags) > 0 && count($oldTags) === 0) {
            $newTag = $currentTags[0];
            if ($newTag->getName() === 'Level 2') {
                $this->eventDispatcher->dispatch(
                    Events::EVENT_MEMBER_KYC_LEVEL_CHANGED,
                    new KycVerificationLevelChangedEvent($member, 'Level 2')
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
