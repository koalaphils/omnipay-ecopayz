<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ApiBundle\Manager;

use ApiBundle\Model\RequestResetPassword;
use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\AuditRevisionLog;
use DbBundle\Entity\MemberReferralName;
use DbBundle\Entity\MemberWebsite;
use DbBundle\Entity\User;
use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\CustomerPaymentOption;
use ApiBundle\Model\AccountActivation as AccountActivationModel;
use ApiBundle\Model\Security as SecurityModel;
use ApiBundle\Model\Register as RegisterModel;
use ApiBundle\Model\RegisterV2\Register as RegisterV2Model;
use ApiBundle\Model\RequestResetPassword as RequestResetPasswordModel;
use ApiBundle\Model\ResetPassword as ResetPasswordModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use ApiBundle\Event\CustomerCreatedEvent;
use Firebase\JWT\JWT;
use MemberBundle\Manager\MemberManager;


/**
 * Description of CustomerManager
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 * @author Paolo Abendanio <cesar.abendanio@zmtsys.com>
 */
class CustomerManager extends AbstractManager
{
    public function passwordIsValid(Customer $customer, string $password): bool
    {
        $user = $customer->getUser();
        $encoder = $this->getEncoderFactory()->getEncoder($user);

        return $encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt());
    }

    public function handleRegister(RegisterModel $registerModel, $originUrl, $locale, $ipAddress, $referrerUrl)
    {
        $fakePinRes = json_decode('{"loginId" : "PS71100000","userCode" : "PS71100000"}');
//        $bank = $registerModel->getBanks();
//        $birthDate = $registerModel->getBirthDateString();
//        $socials = $registerModel->getSocials();
//        $bookies = $registerModel->getBookies();
        $email = trim($registerModel->getEmail());


        $password = trim($registerModel->getPassword());
//        $country = $registerModel->getCountry();
//        $currency = $registerModel->getCurrency();
//        $fName = $registerModel->getFirstName();
//        $mName = trim($registerModel->getMiddleInitial());
//        $lName = $registerModel->getLastName();
//        $contact = trim($registerModel->getContact());
//        $depositMethod = $registerModel->getDepositMethod();
//        $affiliate = trim($registerModel->getAffiliate());
//        $promo = trim($registerModel->getPromo());
        $fName = $mName = $lName = 'default';

        $this->beginTransaction();

        $user = new User();
//        $user->setUsername(uniqid(str_replace(' ', '', $fName . $lName) . '_'));
        $user->setUsername(uniqid(str_replace(' ', '', $fName . $lName) . '_'));
        $user->setEmail($email);
        $user->setType(User::USER_TYPE_MEMBER);
        $user->setRoles(['ROLE_MEMBER' => 2,]);
//        $user->setPhoneNumber($phoneNumber);
        $user->setPreferences([
            'locale' => $locale,
            'ipAddress' => $ipAddress,
            'referrer' => $referrerUrl,
            'originUrl' => $originUrl,
//            'affiliateCode' => $affiliate
        ]);

        $user->setActivationCode(
            $this->getUserManager()->encodeActivationCode($user)
        );
        $user->setActivationSentTimestamp(new \DateTime('now'));
        $user->setZendeskId(null);
        $user->setIsActive(true);
        $user->setPassword(
            $this->getUserManager()->encodePassword($user, $password)
        );

//        $countryEntity = $this->getCountryRepository()->findByCode($country);
//        $currencyEntity = $this->getCurrencyRepository()->findByCode($currency);

        $customer = new Customer();
        $customer->setUser($user);
//        $customer->setBirthDate(\DateTime::createFromFormat('Y-m-d', $birthDate));
//        $customer->setCountry($countryEntity);
//        $customer->setCurrency($currencyEntity);
        $customer->setFName($fName);
        $customer->setMName($mName);
        $customer->setLName($lName);
        $customer->setFullName($fName . ' ' . $mName . ' ' . $lName);
        $customer->setPinLoginId($fakePinRes->loginId);
        $customer->setPinUserCode($fakePinRes->userCode);
//        $customer->setContacts([
//            [
//                'type' => 'mobile',
//                'value' => $contact,
//            ],
//        ]);
        $customer->setIsCustomer(true);
        $customer->setTransactionPassword();
        $customer->setLevel();
//        $customer->setSocials([
//            [
//                'type' => 'skype',
//                'value' => $socials['skype'],
//            ],
//        ]);
//        $customer->setDetails([
//            'affiliate' => [
//                'name' => $affiliate,
//                'code' => $promo,
//            ],
//            'websocket' => [
//                'channel_id' => uniqid($customer->getId() . generate_code(10, false, 'ld')),
//            ],
//            'enabled' => false,
//        ]);
        $customer->setBalance(0);
        $customer->setJoinedAt(new \DateTime('now'));
        $customer->setPhoneNumber();

//        foreach ($bookies as $key => $id) {
//            $userName = $id->getUsername();
//            $productEntity = $this->getProductRepository()->findByCode($id->getCode());
//            $customerProduct = new CustomerProduct();
//            $customerProduct->setProduct($productEntity);
//            $customerProduct->setUsername(!is_null($userName) ? $userName : uniqid('tmp_' . str_replace(' ', '', $productEntity->getName()) . '_'));
//            $customerProduct->setBalance('0.00');
//            $customerProduct->setIsActive(true);
//            $customer->addProduct($customerProduct);
//        }
//
//        $paymentOption = $this->getPaymentOptionRepository()->find($depositMethod);
//        $customerPaymentOption = new CustomerPaymentOption();
//        $customerPaymentOption->setCustomer($customer);
//        $customerPaymentOption->setPaymentOption($paymentOption);
//        $customerPaymentOption->setFields([
//            [
//                'name' => $bank['name'],
//                'account' => $bank['account'],
//                'holder' => $bank['holder'],
//            ],
//        ]);
//        $customer->addPaymentOption($customerPaymentOption);
//        $defaultGroup = $this->getCustomerGroupRepository()->getDefaultGroup();
//        $customer->getGroups()->add($defaultGroup);

        try {
            $this->save($customer);
            $this->commit();
//            $event = new CustomerCreatedEvent($customer, $originUrl);
//            $this->get('event_dispatcher')->dispatch('customer.created', $event);
        } catch (\PDOException $e) {
            $this->rollback();
            throw $e;
        }

        return $customer;
    }

    public function handleRegisterV2(RegisterV2Model $registerModel, $registrationDetails)
    {
        $user = new User();
        $user->setUsername($registerModel->getUsername());
        $user->setEmail($email = $registerModel->getEmail());
        $user->setType(User::USER_TYPE_MEMBER);
        $user->setRoles($registerModel->getRoles());

        $preferences = [
            'locale' => $registrationDetails['locale'],
            'ipAddress' => $registrationDetails['ip'],
            'referrer' => $registrationDetails['referrer'],
            'originUrl' => $originUrl = $registrationDetails['origin'],
            'affiliateCode' => $registerModel->getAffiliateDetail('code'),
            'preferredPaymentGateway' => $registerModel->getPreferredPaymentGateway(),
        ];

        if (array_key_exists('registrationDetails', $registrationDetails)) {
            $preferences['registrationDetails'] = $registrationDetails['registrationDetails'];
        }

        $user->setPreferences($preferences);
        $user->setActivationCode($this->getUserManager()->encodeActivationCode($user));
        $user->setActivationSentTimestamp(new \DateTime('now'));
        $user->setPassword(
            $this->getUserManager()->encodePassword(
                $user,
                $this->getContainer()->getParameter('customer_temp_password')
            )
        );

        $customer = new Customer();
        $customer->setUser($user);
        $customer->setBirthDate($registerModel->getBirthDate());
        $customer->setCountry($registerModel->getCountry());
        $customer->setCurrency($registerModel->getCurrency());
        $customer->setFullName($registerModel->getFullName());
        $customer->setFName('');
        $customer->setLName('');
        $customer->setContacts($registerModel->getContactDetails());
        $customer->setIsCustomer(true);
        $customer->setSocials($registerModel->getSocialDetails());
        $customer->setDetails([
            'affiliate' => $registerModel->getAffiliateDetails(),
            'enabled' => false,
            'websocket' => [
                'channel_id' => uniqid($customer->getId() . generate_code(10, false, 'ld')),
            ],
        ]);
        $customer->setBalance(0);
        $customer->setJoinedAt(new \DateTime('now'));
        $customer->setTags([$registerModel->getTag()]);

        if ($registerModel->hasBookies()) {
            foreach ($registerModel->getBookies() as $bookie) {
                if ($bookie->getProduct()->isAcWallet()) {
                    continue;
                }
                $customerProduct = new CustomerProduct();
                $customerProduct->setProduct($bookie->getProduct());
                $customerProduct->setUsername($bookie->getFormattedUsername());
                $customerProduct->setBalance(0);
                $customerProduct->setIsActive(true);
                $customer->addProduct($customerProduct);
            }
        }

        if ($registerModel->hasDepositMethod()) {
            $customerPaymentOption = new CustomerPaymentOption();
            $customerPaymentOption->setCustomer($customer);
            $customerPaymentOption->setPaymentOption($registerModel->getDepositMethod());
            $customerPaymentOption->setFields([$registerModel->getBankDetails()]);
            $customer->addPaymentOption($customerPaymentOption);
        }

        $defaultGroup = $this->getCustomerGroupRepository()->getDefaultGroup();
        $customer->getGroups()->add($defaultGroup);
        $this->getMemberManager()->createAcWalletForMember($customer);

        $this->beginTransaction();
        try {
            $this->save($customer);
            $this->commit();
            $event = new CustomerCreatedEvent($customer, $originUrl);

            if (null !== $registerModel->getWebsiteUrl()) {
                $member = $event->getCustomer();
                $memberWebsite = new MemberWebsite();
                $memberWebsite->setMember($member);
                $memberWebsite->setWebsite($registerModel->getWebsiteUrl());
                $this->save($memberWebsite);
            }

            if (null !== $registerModel->getPreferredReferralName()) {
                $member = $event->getCustomer();
                $memberReferralName = new MemberReferralName();
                $memberReferralName->setMember($member);
                $memberReferralName->setName($registerModel->getPreferredReferralName());
                $this->save($memberReferralName);
            }

            $this->get('event_dispatcher')->dispatch('customer.created', $event);
        } catch (\PDOException $e) {
            $this->rollback();
            throw $e;
        }

        return $customer;
    }

    /**
     * @param string $name
     * @param string $email
     *
     * @return json
     */
    private function createZendeskUser($name, $email)
    {
        $user = $this->getZendeskManager()->create(
            [
                'name' => $name,
                'email' => $email,
                'verified' => true,
            ]
        );

        return $user;
    }

    public function validateActivationCode($activationCode): array
    {
        $user = $this->getUserRepository()->findByActivationCode($activationCode);

        if ($user !== null) {
            $today = new \DateTime('now');
            $activationSentTimestamp = $user->getActivationSentTimestamp();
            $activationSentTimestamp->modify($this->getParameter('activation.expiration_duration'));

            if ($user->isActivated()) {
                return [
                    'message' => 'Your account has already been activated. Please contact our Customer Service Team for assistance. Thank you.',
                    'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                ];
            } elseif ($activationSentTimestamp < $today) {
                return [
                    'message' => 'Link has already already expired. Please contact our Customer Service Team for assistance. Thank you.',
                    'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                ];
            }
        } else {
            return [
                'message' => 'Activation code is invalid. Please contact Customer Support to request for a new code.',
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            ];
        }

        return ['message' => 'Activation code is valid.', 'user' => $user, 'code' => Response::HTTP_OK];
    }

    public function activateAccount($activationCode, AccountActivationModel $accountActivationModel): array
    {
        $result = $this->validateActivationCode($activationCode);

        if (isset($result['user'])) {
            $user = $result['user'];

            $encoder = $this->getEncoderFactory()->getEncoder($user);
            $user->setUsername($accountActivationModel->getUsername());
            $user->setPassword($encoder->encodePassword($accountActivationModel->getPassword(), $user->getSalt()));
            $user->setActivationTimestamp(new \DateTime('now'));

            $customer = $user->getCustomer();
            $customer->setTransactionPassword($this->encodeTransactionPassword($user, $accountActivationModel->getTransactionPassword()));

            try {
                $this->save($customer);
                $this->getUserManager()->save($user);

                if (!$customer->isTagAsAffiliate()) {
                    $this->getMailer()
                        ->send(
                            $this->getTranslator()->trans('email.subject.activated', [], "AppBundle"),
                            $user->getEmail(),
                            'activated.html.twig',
                            [
                                'firstName' => $customer->getFName(),
                                'customerProducts' => implode(', ', array_column($customer->getCustomerProductNames(), 'productName')),
                            ]
                        );
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $result;
    }

    public function checkEmailIfExists($email): array
    {
        $user = $this->getUserRepository()->findByEmail($email, User::USER_TYPE_MEMBER, false);

        if ($user !== null) {
            return ['message' => 'Email exists.', 'code' => Response::HTTP_UNPROCESSABLE_ENTITY];
        }

        return ['message' => 'Email does not exist.', 'code' => Response::HTTP_OK];
    }

    public function checkUsernameIfExists($username): array
    {
        $user = $this->getUserRepository()->findByUsername($username, User::USER_TYPE_MEMBER);

        if ($user !== null) {
            return ['message' => 'Username exists.', 'code' => 422];
        }

        return ['message' => 'Username does not exists.', 'code' => 200];
    }

    public function handleSecurity(SecurityModel $securityModel)
    {
        $user = $this->getUser();
        $customer = $user->getCustomer();
        $encoder = $this->getEncoderFactory()->getEncoder($user);

        if ($securityModel->getHasChangeUsername()) {
            $user->setUsername($securityModel->getUsername());
        }

        if ($securityModel->getHasChangePassword()) {
            $user->setPassword(
                $encoder->encodePassword(
                    $securityModel->getPassword(), $user->getSalt()
                )
            );
        }

        if ($securityModel->getHasChangeTransactionPassword()) {
            $customer->setTransactionPassword(
                $this->encodeTransactionPassword(
                    $user, $securityModel->getTransactionPassword()
                )
            );
        }


        try {
            $this->save($customer);
            $this->getUserManager()->save($user);
        } catch (\Exception $e) {
            throw $e;
        }

        return $user;
    }

    public function encodeTransactionPassword(User $user, $transactionPassword): string
    {
        return $this->getPasswordEncoder()->encodePassword($user, $transactionPassword, User::getTransactionPasswordSalt());
    }

    public function handleRequestResetPassword(RequestResetPasswordModel $requestResetPasswordModel, string $origin = '')
    {
        $user = $this->getUserRepository()->findByEmail($requestResetPasswordModel->getEmail(), User::USER_TYPE_MEMBER);
        $temporaryPassword = $this->getContainer()->getParameter('customer_temp_password');

        $user->setResetPasswordCode(
            $this->getUserManager()->encodeResetPasswordCode($user)
        );
        $user->setResetPasswordSentTimestamp(new \DateTime());
        $user->setPassword(
            $this->getUserManager()->encodePassword($user, $temporaryPassword)
        );

        try {
            $this->getUserManager()->save($user);

            $resetPasswordCode = [
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'password' => $temporaryPassword,
                'resetPasswordCode' => $user->getResetPasswordCode(),
            ];

            $this->getUserManager()->sendResetPasswordEmail([
                'email' => $user->getEmail(),
                'originFrom' => !empty($origin) ? $origin : $this->getParameter('asianconnect_url'),
                'resetPasswordCode' => JWT::encode($resetPasswordCode, 'AMSV2'),
            ]);
        } catch (\Exception $e) {
            throw $e;
        }

        return $user;
    }

    public function handleResetPassword($resetPasswordCode, $email, ResetPasswordModel $resetPasswordModel): array
    {
        $result = $this->validateResetPasswordCode($resetPasswordCode, $email);

        if (isset($result['user'])) {
            $user = $result['user'];

            $encoder = $this->getEncoderFactory()->getEncoder($user);
            $user->setPassword($encoder->encodePassword($resetPasswordModel->getPassword(), $user->getSalt()));
            $user->setResetPasswordCode(null);
            $user->setResetPasswordSentTimestamp(null);

            try {
                $this->getUserManager()->save($user);
            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $result;
    }

    public function validateResetPasswordCode($resetPasswordCode, $email): array
    {
        $user = $this->getUserRepository()->findByEmailAndResetPasswordCode($resetPasswordCode, $email);

        if ($user !== null) {
            $today = new \DateTime('now');
            $resetPasswordSentTimestamp = $user->getResetPasswordSentTimestamp();
            $resetPasswordSentTimestamp->modify($this->getParameter('reset_password.expiration_duration'));

            if ($resetPasswordSentTimestamp < $today) {
                return [
                    'message' => 'Link has already already expired. Please contact our Customer Service Team for assistance. Thank you.',
                    'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                ];
            }
        } else {
            return [
                'message' => 'Reset password code is invalid. Please contact Customer Support to request for a new code.',
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            ];
        }

        return ['message' => 'Reset password code is valid.', 'user' => $user, 'code' => Response::HTTP_OK];
    }

    public function handleAudit($category)
    {
        $auditManager = $this->getAuditManager();
        $user = $this->getUser();

        try {
            $operation = AuditRevisionLog::OPERATION_LOGIN;
            $auditCategory = AuditRevisionLog::CATEGORY_LOGIN;

            if ($category == 'logout') {
                $operation = AuditRevisionLog::OPERATION_LOGOUT;
                $auditCategory = AuditRevisionLog::CATEGORY_LOGOUT;
            }

            $auditManager->audit($user, $operation, $auditCategory);
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), $e->getCode()];
        }

        return ['user' => $user, 'code' => Response::HTTP_OK];
    }

    protected function getRepository()
    {
    }

    private function getUserRepository(): \DbBundle\Repository\UserRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:User');
    }

    private function getEncoderFactory(): EncoderFactoryInterface
    {
        return $this->getContainer()->get('security.encoder_factory');
    }

    protected function getPasswordEncoder()
    {
        return $this->get('security.password_encoder');
    }

    private function getAuditManager(): \AuditBundle\Manager\AuditManager
    {
        return $this->getContainer()->get('audit.manager');
    }

    private function getUserManager(): \UserBundle\Manager\UserManager
    {
        return $this->getContainer()->get('user.manager');
    }

    private function getProductRepository(): \ApiBundle\Repository\ProductRepository
    {
        return $this->getContainer()->get('api.product_repository');
    }

    private function getCountryRepository(): \DbBundle\Repository\CountryRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Country');
    }

    private function getCurrencyRepository(): \DbBundle\Repository\CurrencyRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Currency');
    }

    private function getZendeskManager(): \ZendeskBundle\Manager\UserManager
    {
        return $this->getContainer()->get('zendesk.user_manager');
    }

    private function getMediaManager(): \MediaBundle\Manager\MediaManager
    {
        return $this->getContainer()->get('media.manager');
    }

    private function getMailer(): \AppBundle\Manager\MailerManager
    {
        return $this->getContainer()->get('app.mailer_manager');
    }

    private function getPaymentOptionRepository(): \DbBundle\Repository\PaymentOptionRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:PaymentOption');
    }

    protected function getTranslator(): \Symfony\Component\Translation\TranslatorInterface
    {
        return $this->getContainer()->get('translator');
    }

    private function getCustomerGroupRepository(): \DbBundle\Repository\CustomerGroupRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerGroup');
    }

    private function getMemberManager(): MemberManager
    {
        return $this->getContainer()->get('member.manager');
    }
}
