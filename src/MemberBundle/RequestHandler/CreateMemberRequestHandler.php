<?php

namespace MemberBundle\RequestHandler;

use DbBundle\Entity\Country;
use DbBundle\Entity\Currency;
use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerGroup;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\Product;
use DbBundle\Entity\User;
use DbBundle\Repository\CustomerGroupRepository;
use DbBundle\Repository\ProductRepository;
use Doctrine\ORM\EntityManager;
use MemberBundle\Manager\MemberManager;
use MemberBundle\Request\CreateMemberRequest;
use PinnacleBundle\Service\PinnacleService;
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
    private $pinnacleService;

    public function __construct(
        EntityManager $entityManager,
        RequestStack $requestStack,
        UserPasswordEncoder $passwordEncoder,
        MemberManager $memberManager,
        UserManager $userManager,
        PinnacleService $pinnacleService,
        string $asianconnectUrl
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->passwordEncoder = $passwordEncoder;
        $this->memberManager = $memberManager;
        $this->userManager = $userManager;
        $this->asianconnectUrl = $asianconnectUrl;
        $this->pinnacleService = $pinnacleService;
    }

    public function handle(CreateMemberRequest $request)
    {
        $user = new User();
        $country = null;
        if ($request->isUseEmail()) {
            $username = $request->getEmail();
            $user->setSignupType(User::SIGNUP_TYPE_EMAIL);
        } else {
            $country = $this->entityManager->getPartialReference(Country::class, $request->getCountry());
            $username = str_replace('+', '', $country->getPhoneCode() . $request->getPhoneNumber());
        }

        if ($request->getCountry() !== null && $country === null) {
            $country = $this->entityManager->getPartialReference(Country::class, $request->getCountry());
        }

        $memberProduct = new CustomerProduct();
        $memberProduct->setBalance('0.00');
        $memberProduct->setIsActive(true);

        $user->setUsername($username);
        $user->setEmail($request->getEmail());
        $user->setIsActive($request->getStatus());
        $user->setType($request->getUserType());
        $user->setPassword($this->encodePassword($user, $request->getPassword()));
        $user->setPreference('ipAddress', $this->getClientIp());
        $user->setActivationSentTimestamp(new \DateTime('now'));
        $user->setActivationTimestamp(new \DateTime('now'));
        $user->setRoles(['ROLE_MEMBER' => 2]);

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
        $member->setCountry($country);
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

        if ($request->getUserType() == User::USER_TYPE_AFFILIATE) {
            $piwiWallet = $this->getProductRepository()->getPiwiWalletProduct();
            $memberProduct->setProduct($piwiWallet);
            $memberProduct->setUserName($this->generateUsername($username));
        } else {
            $pinnacleProduct = $this->pinnacleService->getPinnacleProduct();
            $pinnaclePlayer = $this->pinnacleService->getPlayerComponent()->createPlayer();
            $memberProduct->setProduct($pinnacleProduct);
            $memberProduct->setUserName($pinnaclePlayer->userCode());
            $member->setPinLoginId($pinnaclePlayer->loginId());
            $member->setPinUserCode($pinnaclePlayer->userCode());
        }

        $member->setTags([]);
        $member->setUser($user);
        $user->setCustomer($member);
        $member->setTags([Customer::ACRONYM_MEMBER]);
        $member->addProduct($memberProduct);

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

    private function getProductRepository(): ProductRepository
    {
        return $this->entityManager->getRepository(Product::class);
    }

    private function generateUsername(string $username): string
    {
        return strtok($username, '@');
    }
}
