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

class CreateMemberRequestHandler
{
    private $entityManager;
    private $requestStack;
    private $passwordEncoder;
    private $memberManager;

    public function __construct(
        EntityManager $entityManager,
        RequestStack $requestStack,
        UserPasswordEncoder $passwordEncoder,
        MemberManager $memberManager
    )
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->passwordEncoder = $passwordEncoder;
        $this->memberManager = $memberManager;
    }

    public function handle(CreateMemberRequest $request)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://47.254.197.223:9000/api/pinnacle/users');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json')); // Assuming you're requesting JSON
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);

        $data = json_decode($response);

        $user = new User();
        $user->setUsername($request->getUsername());
        $user->setEmail($request->getEmail());
        $user->setIsActive($request->getStatus());
        $user->setType(User::USER_TYPE_MEMBER);
        $user->setPassword($this->encodePassword($user, $request->getPassword()));
        $user->setPreference('ipAddress', $this->getClientIp());

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
//        $member->setCurrency($this->entityManager->getPartialReference(Currency::class, $request->getCurrency()));
//        $member->setBirthDate($request->getBirthDate());
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

        if (isset($data->userCode) && $data->userCode != '') {
            $member->setPinUserCode($data->userCode);
        } else {
            $member->setPinUserCode('userCodeTest');
        }

        if (isset($data->loginId) && $data->loginId != '') {
            $member->setPinLoginId($data->loginId);
        } else {
            $member->setPinUserCode('loginIdTest');
        }
        $this->memberManager->createAcWalletForMember($member);
        $member->setTags([Customer::ACRONYM_MEMBER]);

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
