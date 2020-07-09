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
use DbBundle\Repository\CountryRepository;
use DbBundle\Repository\CurrencyRepository;
use DbBundle\Repository\ProductRepository;
use Doctrine\ORM\EntityManager;
use MemberBundle\Manager\MemberManager;
use MemberBundle\Request\CreateMemberRequest;
use PinnacleBundle\Service\PinnacleService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use UserBundle\Manager\UserManager;
use ApiBundle\Service\JWTGeneratorService;
use ProductIntegrationBundle\ProductIntegrationFactory;

class CreateMemberRequestHandler
{
    private $entityManager;
    private $requestStack;
    private $passwordEncoder;
    private $memberManager;
    private $userManager;
    private $asianconnectUrl;
    private $pinnacleService;
    private $jwtGeneratorService;
    private $productIntegrationFactory;

    public function __construct(
        EntityManager $entityManager,
        RequestStack $requestStack,
        UserPasswordEncoder $passwordEncoder,
        MemberManager $memberManager,
        UserManager $userManager,
        PinnacleService $pinnacleService,
        JWTGeneratorService $jwtGeneratorService,
        ProductIntegrationFactory $productIntegrationFactory,
        string $asianconnectUrl
    ) {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->passwordEncoder = $passwordEncoder;
        $this->memberManager = $memberManager;
        $this->userManager = $userManager;
        $this->asianconnectUrl = $asianconnectUrl;
        $this->pinnacleService = $pinnacleService;
        $this->jwtGeneratorService = $jwtGeneratorService;
        $this->productIntegrationFactory = $productIntegrationFactory;
    }

    public function handle(CreateMemberRequest $request)
    {
        $user = new User();
        $country = null;
        if ($request->isUseEmail()) {
            $username = $request->getEmail();
            $user->setSignupType(User::SIGNUP_TYPE_EMAIL);
        } else {
            $country = $this->getCountryRepository()->findById($request->getCountry());
            $username = str_replace('+', '', $country->getPhoneCode() . $request->getPhoneNumber());
        }

        if ($request->getCountry() !== null && $country === null) {
            $country = $this->getCountryRepository()->findById($request->getCountry());
        }

        $user->setUsername($username);
        $user->setEmail($request->getEmail());
        $user->setIsActive($request->getStatus());
        $user->setType($request->getUserType());
        $user->setPassword($this->encodePassword($user, $request->getPassword()));
        $user->setPreference('ipAddress', $this->getClientIp());
        $user->setActivationSentTimestamp(new \DateTime('now'));
        $user->setActivationTimestamp(new \DateTime('now'));
        $user->setRoles(['ROLE_MEMBER' => 2]);
        $user->setPhoneNumber($request->getPhoneNumber());

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

        $currency = $this->getCurrencyRepository()->findById($request->getCurrency());
        $member->setCurrency($currency);

        $member->setCountry($country);
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
            $memberProduct = new CustomerProduct();
            $memberProduct->setBalance('0.00');
            $memberProduct->setIsActive(true);
            $memberProduct->setProduct($piwiWallet);
            $memberProduct->setUserName($this->generateUsername($username));
            $member->addProduct($memberProduct);
        } else {
            $pinnacleProduct = $this->pinnacleService->getPinnacleProduct();
            $pinnaclePlayer = $this->pinnacleService->getPlayerComponent()->createPlayer();
            $memberPinProduct = new CustomerProduct();
            $memberPinProduct->setBalance('0.00');
            $memberPinProduct->setIsActive(true);
            $memberPinProduct->setProduct($pinnacleProduct);
            $memberPinProduct->setUserName($pinnaclePlayer->userCode());
            $member->setPinLoginId($pinnaclePlayer->loginId());
            $member->setPinUserCode($pinnaclePlayer->userCode());
            $member->addProduct($memberPinProduct);

            $walletProduct = $this->getProductRepository()->getProductByCode(Product::MEMBER_WALLET_CODE);
            $memberWalletProduct = new CustomerProduct();
            $memberWalletProduct->setProduct($walletProduct);
            $memberWalletProduct->setUsername(Product::MEMBER_WALLET_CODE . '_' . uniqid());
            $memberWalletProduct->setBalance('0.00');
            $memberWalletProduct->setIsActive(true);
            $member->addProduct($memberWalletProduct);

            $integration = $this->productIntegrationFactory->getIntegration(Product::EVOLUTION_PRODUCT_CODE);
            $evolutionProduct = $this->getProductRepository()->getProductByCode(Product::EVOLUTION_PRODUCT_CODE);
            $memberEvoProduct = new CustomerProduct();
            $memberEvoProduct->setProduct($evolutionProduct);
            $memberEvoProduct->setUsername('Evolution_' . uniqid());
            $memberEvoProduct->setBalance('0.00');
            $memberEvoProduct->setIsActive(true);
            $member->addProduct($memberEvoProduct);

            try {
                $jwt = $this->jwtGeneratorService->generate([]);
                $integration->auth($jwt, [
                    'id' => $memberEvoProduct->getUsername(),
                    'lastName' => $request->getFullName() ? $request->getFullName() : $username,
                    'firstName' => $request->getFullName() ? $request->getFullName() : $username,
                    'nickname' => str_replace("Evolution_","", $memberEvoProduct->getUsername()),
                    'country' => $country ? $country->getCode() : 'UK',
                    'language' => 'en',
                    'currency' => $currency->getCode(),
                    'ip' => $this->getClientIp(),
                    'sessionId' => $this->getSessionId(),
                ]);
            } catch (\Exception $ex) {
                throw new \Exception('Failed to create EVO Account.', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $member->setTags([]);
        $member->setUser($user);
        $user->setCustomer($member);
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

    private function getSessionId(): string
    {
        return $this->requestStack->getCurrentRequest()->getSession()->getId();
    }

    private function encodePassword(User $user, $password): string
    {
        return $this->passwordEncoder->encodePassword($user, $password);
    }

    private function getGroupRepository(): CustomerGroupRepository
    {
        return $this->entityManager->getRepository(CustomerGroup::class);
    }

    private function getCountryRepository(): CountryRepository
    {
        return $this->entityManager->getRepository(Country::class);
    }

    private function getCurrencyRepository(): CurrencyRepository
    {
        return $this->entityManager->getRepository(Currency::class);
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
