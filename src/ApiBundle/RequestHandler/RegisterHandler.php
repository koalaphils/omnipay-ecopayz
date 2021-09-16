<?php

declare(strict_types = 1);

namespace ApiBundle\RequestHandler;

use ApiBundle\Request\RegisterRequest;
use AppBundle\Helper\Publisher;
use AppBundle\Manager\MailerManager;
use AppBundle\Manager\SettingManager;
use CountryBundle\Manager\CountryManager;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\MemberWebsite;
use DbBundle\Entity\Product;
use DbBundle\Entity\User;
use DbBundle\Repository\CountryRepository;
use DbBundle\Repository\CurrencyRepository;
use DbBundle\Repository\CustomerGroupRepository;
use DbBundle\Repository\MemberWebsiteRepository;
use DbBundle\Repository\ProductRepository;
use DbBundle\Repository\TwoFactorCodeRepository;
use Doctrine\ORM\EntityManager;
use PinnacleBundle\Service\PinnacleService;
use TwoFactorBundle\Provider\Message\StorageInterface;
use UserBundle\Manager\UserManager;
use ProductIntegrationBundle\ProductIntegrationFactory;
use ProductInegrationBundle\Integration\PinnaclePlayerInterface;

class RegisterHandler
{
    private $pinnacleService;
    private $userManager;
    private $currencyRepository;
    private $countryRepository;
    private $countryManager;
    private $productRepository;
    private $settingManager;
    private $entityManager;
    private $codeStorage;
    private $twoFactorCodeRepository;
    private $memberGroupRepository;
    private $publisher;
    private $memberWebsiteRepository;
    private $mailerManager;
    private $productIntegrationFactory;

    public function __construct(
        PinnacleService $pinnacleService,
        UserManager $userManager,
        CurrencyRepository $currencyRepository,
        CountryRepository $countryRepository,
        CountryManager $countryManager,
        ProductRepository $productRepository,
        CustomerGroupRepository $memberGroupRepository,
        SettingManager $settingManager,
        EntityManager $entityManager,
        StorageInterface $codeStorage,
        TwoFactorCodeRepository $twoFactorCodeRepository,
        Publisher $publisher,
        MemberWebsiteRepository $memberWebsiteRepository,
        MailerManager $mailerManager,
        ProductIntegrationFactory $productIntegrationFactory
    ) {
        $this->pinnacleService = $pinnacleService;
        $this->userManager = $userManager;
        $this->countryRepository = $countryRepository;
        $this->currencyRepository = $currencyRepository;
        $this->productRepository = $productRepository;
        $this->settingManager = $settingManager;
        $this->entityManager = $entityManager;
        $this->countryManager = $countryManager;
        $this->codeStorage = $codeStorage;
        $this->twoFactorCodeRepository = $twoFactorCodeRepository;
        $this->publisher = $publisher;
        $this->memberGroupRepository = $memberGroupRepository;
        $this->memberWebsiteRepository = $memberWebsiteRepository;
        $this->mailerManager = $mailerManager;
        $this->productIntegrationFactory =$productIntegrationFactory;
    }

    public function handle(RegisterRequest $registerRequest): Member
    {
        $member = $this->generateMember($registerRequest);
        $this->entityManager->beginTransaction();
        try {
            $this->changeCodeAsUsed($registerRequest->getVerificationCode());
            $this->entityManager->persist($member);
            $this->entityManager->flush($member);

            $this->entityManager->commit();

            try {
                $this->sendEmail($registerRequest);

                $this->publisher->publishUsingWamp('member.registered', [
                    'message' => $member->getUser()->getUsername() . ' was registered',
                    'title' => 'New Member',
                    'otherDetails' => [
                        'id' => $member->getId(),
                        'type' => 'profile'
                    ]
                ]);
            } catch (\Exception $exception) {
                // Do nothing
            }
        } catch (\PDOException $ex) {
            $this->entityManager->rollback();
            throw $ex;
        }

        return $member;
    }

    private function changeCodeAsUsed(string $code): void
    {
        $codeModel = $this->twoFactorCodeRepository->getCode($code);
        $codeModel->setToUsed();
        $this->codeStorage->saveCode($codeModel);
    }

    private function sendEmail(RegisterRequest $registerRequest): void
    {
        $payload = [
            'provider' => $registerRequest->getEmail() === '' ?  'phone' : 'email',
            'phone' => $registerRequest->getEmail() === '' ?  $registerRequest->getCountryPhoneCode() . $registerRequest->getPhoneNumber() : '',
            'email' => $registerRequest->getEmail() !== '' ? $registerRequest->getEmail() : '',
            'ip' => $registerRequest->getIpAddress(),
            'from' => $registerRequest->getEmail() === '' ?   $registerRequest->getCountryPhoneCode() . $registerRequest->getPhoneNumber() : $registerRequest->getEmail(),
        ];

        $subject = $this->settingManager->getSetting('registration.mail.subject');
        $to = $this->settingManager->getSetting('registration.mail.to');

        $this->mailerManager->send($subject, $to, 'registered.html.twig', $payload);
    }

    private function generateMember(RegisterRequest $registerRequest): Member
    {
        $now = new \DateTime('now');
        $user = $this->generateUser($registerRequest);
        $user->setActivationSentTimestamp($now);
        $user->setActivationTimestamp($now);
        $defaultMemberGroup = $this->memberGroupRepository->getDefaultGroup();
        $currency = $this->currencyRepository->findByCode($registerRequest->getCurrency());
        $pinnacleProduct = $this->getPinnacleProduct();

        $member = new Member();
        $member->setUser($user);
        $member->setCurrency($currency);
        $member->setIsCustomer(true);
        $member->setTransactionPassword();
        $member->setLevel();
        $member->setBalance(0);
        $member->setJoinedAt($now);
        $member->setFName('');
        $member->setLName('');
        $member->setFullName('');

        // Create Pinnacle
        try {
            $integration = $this->productIntegrationFactory->getIntegration('pinbet');
            $response = $integration->create();

            $memberProduct = new MemberProduct();
            $memberProduct->setProduct($pinnacleProduct);
            $memberProduct->setUserName($response['user_code']);
            $memberProduct->setBalance('0.00');
            $memberProduct->setIsActive(true);
            
            $member->setPinLoginId($response['login_id']);
            $member->setPinUserCode($response['user_code']);  

            $member->addProduct($memberProduct);
        } catch(\Exception $ex) {
            //Catch generic exception since we on what error to throw.
        }       
       
        $member->addGroup($defaultMemberGroup);
        $member->setDetails([
            'websocket' => [
                'channel_id' => uniqid(generate_code(10, false, 'ld')),
            ],
            'registration' => [
                'ip' => $registerRequest->getIpAddress(),
                'locale' => $registerRequest->getLocale(),
                'referrer_url' => $registerRequest->getReferrerUrl(),
                'referrer_origin_url' => $registerRequest->getOriginUrl(),
                'site' => $registerRequest->getRegistrationSite(),
            ]
        ]);
        $member->setLocale($registerRequest->getLocale());

        /*if ($registerRequest->getReferrerUrl() !== '') {
            $affiliate = $this->getAffiliateByWebsite($registerRequest->getReferrerUrl());
            if ($affiliate instanceof Customer) {
                $member->setAffiliate($affiliate);
            }
        }*/

        if ($registerRequest->getCountryPhoneCode() !== '') {
            $country = $this->countryManager->getCountryByCallingCode($registerRequest->getCountryPhoneCode());
            $member->setCountry($country);
        }

        return $member;
    }

    private function getAffiliateByWebsite(string $website): ?Customer
    {
        $memberWebsite = $this->memberWebsiteRepository->findOneByWebsite($website);
        if ($memberWebsite instanceof MemberWebsite) {
          return $memberWebsite->getMember();
        }

        return null;
    }

    private function generateUser(RegisterRequest $registerRequest): User
    {
        $user = new User();
        if ($registerRequest->getEmail() === '') {
            $user->setSignupType(User::SIGNUP_TYPE_PHONE);
            $user->setUsername(str_replace('+', '', $registerRequest->getPhoneWithCountryCode()));
            $user->setPhoneNumber($registerRequest->getCountryPhoneCode() . $registerRequest->getPhoneNumber());
        } else {
            $user->setSignupType(User::SIGNUP_TYPE_EMAIL);
            $user->setEmail($registerRequest->getEmail());
            $user->setUsername($registerRequest->getEmail());
            $user->setPhoneNumber($registerRequest->getCountryPhoneCode() . $registerRequest->getPhoneNumber());
        }
        $user->setType(User::USER_TYPE_MEMBER);
        $user->setRoles(['ROLE_MEMBER' => 2]);

        $user->setActivationCode($this->userManager->encodeActivationCode($user));
        $user->setIsActive(true);
        $user->setPassword($this->userManager->encodePassword($user, $registerRequest->getPassword()));

        return $user;
    }

    private function getPinnacleProduct(): Product
    {
        $productCode = $this->settingManager->getSetting('pinnacle.product');

        return $this->productRepository->getProductByCode($productCode);
    }
}