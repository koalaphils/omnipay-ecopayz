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
use PromoBundle\Manager\PromoManager;

class UpdateProfileRequestHandler
{
    private $entityManager;
    private $requestStack;
    private $auditManager;
    private $memberManager;
    private $affiliateService;
    private $promoManager;

    public function __construct(
        EntityManager $entityManager,
        RequestStack $requestStack,
        AuditManager $auditManager,
        MemberManager $memberManager,
        AffiliateService $affiliateService,
        PromoManager $promoManager
    )
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
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
        
        foreach ($request->getGroups() as $groupId) {
            $group = $this->getCustomerGroupRepository()->findOneById($groupId);
            $customer->addGroup($group);
        }

        $this->processAffiliate($request, $customer);
        
        $this->entityManager->persist($customer->getUser());
        $this->entityManager->flush($customer->getUser());
        $this->entityManager->persist($customer);
        $this->entityManager->flush($customer);
        $this->promoManager->createPersonalLinkId($customer);

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
    private function getCustomerGroupRepository(): CustomerGroupRepository
    {
        return $this->entityManager->getRepository(CustomerGroup::class);
    }

    private function getUserRepository(): UserRepository
    {
        return $this->entityManager->getRepository(User::class);
    }
}
