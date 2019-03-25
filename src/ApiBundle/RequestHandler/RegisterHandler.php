<?php

declare(strict_types = 1);

namespace ApiBundle\RequestHandler;

use ApiBundle\Request\RegisterRequest;
use AppBundle\Manager\SettingManager;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\Product;
use DbBundle\Entity\TwoFactorCode;
use DbBundle\Entity\User;
use DbBundle\Repository\CountryRepository;
use DbBundle\Repository\CurrencyRepository;
use DbBundle\Repository\ProductRepository;
use DbBundle\Repository\TwoFactorCodeRepository;
use Doctrine\ORM\EntityManager;
use PinnacleBundle\Component\Exceptions\PinnacleException;
use PinnacleBundle\Service\PinnacleService;
use TwoFactorBundle\Provider\Message\StorageInterface;
use UserBundle\Manager\UserManager;

class RegisterHandler
{
    /**
     * @var PinnacleService
     */
    private $pinnacleService;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;

    /**
     * @var CountryRepository
     */
    private $countryRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var SettingManager
     */
    private $settingManager;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var StorageInterface
     */
    private $codeStorage;

    /**
     * @var TwoFactorCodeRepository
     */
    private $twoFactorCodeRepository;

    public function __construct(
        PinnacleService $pinnacleService,
        UserManager $userManager,
        CurrencyRepository $currencyRepository,
        CountryRepository $countryRepository,
        ProductRepository $productRepository,
        SettingManager $settingManager,
        EntityManager $entityManager,
        StorageInterface $codeStorage,
        TwoFactorCodeRepository $twoFactorCodeRepository
    ) {
        $this->pinnacleService = $pinnacleService;
        $this->userManager = $userManager;
        $this->countryRepository = $countryRepository;
        $this->currencyRepository = $currencyRepository;
        $this->productRepository = $productRepository;
        $this->settingManager = $settingManager;
        $this->entityManager = $entityManager;
        $this->codeStorage = $codeStorage;
        $this->twoFactorCodeRepository = $twoFactorCodeRepository;
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

    private function generateMember(RegisterRequest $registerRequest): Member
    {
        $now = new \DateTime('now');
        $user = $this->generateUser($registerRequest);
        $user->setActivationSentTimestamp($now);
        $currency = $this->currencyRepository->findByCode($registerRequest->getCurrency());
        $pinnacleProduct = $this->getPinnacleProduct();
        $pinnaclePlayer = $this->pinnacleService->getPlayerComponent()->createPlayer();

        $memberProduct = new MemberProduct();
        $memberProduct->setProduct($pinnacleProduct);
        $memberProduct->setUserName($pinnaclePlayer->userCode());
        $memberProduct->setBalance('0.00');
        $memberProduct->setIsActive(true);

        $member = new Member();
        $member->setUser($user);
        $member->setCurrency($currency);
        $member->setPinLoginId($pinnaclePlayer->loginId());
        $member->setPinUserCode($pinnaclePlayer->userCode());
        $member->setIsCustomer(true);
        $member->setTransactionPassword();
        $member->setLevel();
        $member->setBalance(0);
        $member->setJoinedAt($now);
        $member->setFName('');
        $member->setLName('');
        $member->setFullName('');
        $member->addProduct($memberProduct);
        $member->setDetails([
            'websocket' => [
                'channel_id' => uniqid(generate_code(10, false, 'ld')),
            ],
        ]);

        if ($registerRequest->getCountryPhoneCode() !== '') {
            $country = $this->countryRepository->findByPhoneCode($registerRequest->getCountryPhoneCode());
            $member->setCountry($country);
        }

        return $member;
    }

    private function generateUser(RegisterRequest $registerRequest): User
    {
        $user = new User();
        if ($registerRequest->getEmail() === '') {
            $user->setSignupType(User::SIGNUP_TYPE_PHONE);
            $user->setUsername($registerRequest->getPhoneWithCountryCode());
        } else {
            $user->setSignupType(User::SIGNUP_TYPE_EMAIL);
            $user->setEmail($registerRequest->getEmail());
            $user->setUsername($registerRequest->getEmail());
        }
        $user->setType(User::USER_TYPE_MEMBER);
        $user->setRoles(['ROLE_MEMBER' => 2]);
        $user->setPreferences([
            'locale' => $registerRequest->getLocale(),
            'ipAddress' => $registerRequest->getIpAddress(),
            'referrer' => $registerRequest->getReferrerUrl(),
            'originUrl' => $registerRequest->getOriginUrl(),
        ]);

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