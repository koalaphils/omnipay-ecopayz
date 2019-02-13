<?php

namespace MemberBundle\RequestHandler;

use DbBundle\Entity\Country;
use DbBundle\Entity\Currency;
use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerGroup;
use DbBundle\Entity\User;
use DbBundle\Repository\CustomerGroupRepository;
use Doctrine\ORM\EntityManager;
use MemberBundle\Manager\MemberManager;
use MemberBundle\Request\CreateMemberRequest;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use UserBundle\Manager\UserManager;

class CreateMemberRequestHandler
{
    private $entityManager;
    private $requestStack;
    private $passwordEncoder;
    private $memberManager;
    private $userManager;
    private $asianconnectUrl;

    public function __construct(
        EntityManager $entityManager,
        RequestStack $requestStack,
        UserPasswordEncoder $passwordEncoder,
        MemberManager $memberManager,
        UserManager $userManager,
        string $asianconnectUrl
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->passwordEncoder = $passwordEncoder;
        $this->memberManager = $memberManager;
        $this->userManager = $userManager;
        $this->asianconnectUrl = $asianconnectUrl;
    }

    public function handle(CreateMemberRequest $request)
    {
        $user = new User();
        $user->setUsername($request->getUsername());
        $user->setEmail($request->getEmail());
        $user->setIsActive($request->getStatus());
        $user->setType(User::USER_TYPE_MEMBER);
        $user->setPassword($this->encodePassword($user, $request->getPassword()));
        $user->setPreference('ipAddress', $this->getClientIp());
        $user->setActivationSentTimestamp(new \DateTime('now'));
        $user->setActivationTimestamp(new \DateTime('now'));

        $member = new Customer();
        $member->setFullName($request->getFullName());
        $member->setFName($request->getFullName());
        if (empty($request->getGroups())) {
            $member->addGroup($this->getDefaultGroup());
        } else {
            foreach ($request->getGroups() as $groupId) {
                $member->addGroup($this->entityManager->getPartialReference(CustomerGroup::class, $groupId));
            }
        }
        if ($request->getReferal() !== null) {
            $member->setAffiliate($this->entityManager->getPartialReference(Customer::class, $request->getReferal()));
        }
        $member->setCountry($this->entityManager->getPartialReference(Country::class, $request->getCountry()));
        $member->setCurrency($this->entityManager->getPartialReference(Currency::class, $request->getCurrency()));
        $member->setBirthDate($request->getBirthDate());
        $member->setGender($request->getGender());
        $member->setJoinedAt($request->getJoinedAt());
        $member->setTransactionPassword($this->encodePassword($user, ''));
        $member->setIsAffiliate(true);
        $member->setIsCustomer(true);
        $member->setDetails([
            'websocket' => [
                'channel_id' => uniqid($member->getId() . generate_code(10, false, 'ld')),
            ],
            'enabled' => false,
        ]);
        $member->setTags([]);

        $member->setUser($user);
        $user->setCustomer($member);
        $this->memberManager->createAcWalletForMember($member);
        $member->setTags([Customer::ACRONYM_MEMBER]);

        $this->userManager->sendActivationMail(
            [
                'username' => $user->getUsername(),
                'password' => $request->getPassword(),
                'email' => $request->getEmail(),
                'fullName' => $request->getFullName(),
                'originFrom' => $this->asianconnectUrl,
                'isAffiliate' => false,
            ]
        );

        $this->entityManager->persist($member);
        $this->entityManager->flush($member);

        return $member;
    }

    private function getDefaultGroup(): CustomerGroup
    {
        return $this->getGroupRepository()->getDefaultGroup();
    }

    private function getClientIp(): string
    {
        return $this->requestStack->getCurrentRequest()->getClientIp();
    }

    private function encodePassword(User $user, $password): string
    {
        return $this->passwordEncoder->encodePassword($user, $password);
    }

    private function getGroupRepository(): CustomerGroupRepository
    {
        return $this->entityManager->getRepository(CustomerGroup::class);
    }
}
