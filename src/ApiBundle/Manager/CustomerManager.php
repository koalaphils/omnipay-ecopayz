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

    // zimi
    public function passwordIsValidByUser(User $user, string $password): bool
    {        
        $encoder = $this->getEncoderFactory()->getEncoder($user);

        return $encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt());
    }

    public function handleRegister(RegisterModel $registerModel, $originUrl, $locale, $ipAddress, $referrerUrl)
    {        
//        $bank = $registerModel->getBanks();
//        $birthDate = $registerModel->getBirthDateString();
//        $socials = $registerModel->getSocials();
//        $bookies = $registerModel->getBookies();


//        $country = $registerModel->getCountry();
//        $currency = $registerModel->getCurrency();
//        $fName = $registerModel->getFirstName();
//        $mName = trim($registerModel->getMiddleInitial());
//        $lName = $registerModel->getLastName();
//        $contact = trim($registerModel->getContact());
//        $depositMethod = $registerModel->getDepositMethod();
//        $affiliate = trim($registerModel->getAffiliate());
//        $promo = trim($registerModel->getPromo());        

        $pinUserCode = $registerModel->getPinUserCode();
        $pinLoginId = $registerModel->getPinLoginId();
        $email = $registerModel->getEmail() ? trim($registerModel->getEmail()) : null;
        $phoneNumber = $registerModel->getPhoneNumber() ? trim($registerModel->getPhoneNumber()) : null;
        
        // if (isset($email) && $email != '') {
        //     $type = $email;
        // } elseif (isset($phoneNumber) && $phoneNumber != '') {
        //     $type = $phoneNumber;
        // } else {
        //     $type = '';
        // }

        $password = trim($registerModel->getPassword());
        $countryPhoneCode = $registerModel->getCountryPhoneCode();

        $fName = 'firstName';
        $mName = 'middleName';
        $lName = 'lastName';

        $this->beginTransaction();

        $user = new User();        
        $signupType = $registerModel->getSignupType();
        $user->setSignupType($signupType);
        if ($signupType == 0) {           
            $username = $countryPhoneCode . substr($phoneNumber, 1);
        } else {
            $username = $email;
        }
        
        $user->setUsername($username);        
        $user->setEmail($email);
        $user->setPhoneNumber($phoneNumber);
        $user->setType(User::USER_TYPE_MEMBER);
        $user->setRoles(['ROLE_MEMBER' => 2,]);
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

        $countryEntity = $this->getCountryRepository()->findByPhoneCode($countryPhoneCode);
        $currencyEntity = $this->getCurrencyRepository()->findByCode('EUR');
        $customer = new Customer();
        $customer->setUser($user);
        $customer->setBirthDate(\DateTime::createFromFormat('Y-m-d', date('Y-m-d')));
        $customer->setCountry($countryEntity);
        $customer->setCurrency($currencyEntity);
        $customer->setFName($fName);
        $customer->setMName($mName);
        $customer->setLName($lName);

        $customer->setFullName("Customer" . " " . $pinLoginId);
        $customer->setPinLoginId($pinLoginId);
        $customer->setPinUserCode($pinUserCode);
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

        $tempPassword = $registerModel->getTempPassword();

        if (array_key_exists('registrationDetails', $registrationDetails)) {
            $preferences['registrationDetails'] = $registrationDetails['registrationDetails'];
        }
        $user->setPreferences($preferences);
        $user->setActivationCode($this->getUserManager()->encodeActivationCode($user));
        $user->setActivationSentTimestamp(new \DateTime('now'));
        $user->setActivationTimestamp(new \DateTime('now'));
        $user->setPassword(
            $this->getUserManager()->encodePassword(
                $user,
                $tempPassword
            )
        );
        $user->setPlainPassword($tempPassword);

        $customer = new Customer();
        $customer->setUser($user);
        $customer->setBirthDate($registerModel->getBirthDate());
        $customer->setCountry($registerModel->getCountry());
        $customer->setCurrency($registerModel->getCurrency());
        $customer->setFullName($registerModel->getFullName());
        $customer->setFName('');
        $customer->setLName('');
        $customer->setGender(Customer::MEMBER_GENDER_NOT_SET);
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
            $event = new CustomerCreatedEvent($customer, $originUrl, $tempPassword);

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

    public function activateAccount(string $activationCode, AccountActivationModel $accountActivationModel): array
    {
        $result = $this->validateActivationCode($activationCode);

        if (isset($result['user'])) {
            $user = $result['user'];

            $encoder = $this->getEncoderFactory()->getEncoder($user);
            $user->setUsername($accountActivationModel->getUsername());
            $user->setPassword($encoder->encodePassword($accountActivationModel->getPassword(), $user->getSalt()));
            $user->setActivationTimestamp(new \DateTime('now'));
            $user->setActivationCode('');

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
                                'fullName' => $customer->getFullName(),
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

    public function checkPinUserCodeIfExists($pinUserCode): array
    {
        //TODO : need to load customer repository by function like other
        $user = $this->getDoctrine()->getRepository('DbBundle:Customer')->findByPinUserCode($pinUserCode);

        if ($user !== null) {
            return ['message' => 'Pin user code exists.', 'exist' => true, 'code' => Response::HTTP_UNPROCESSABLE_ENTITY];
        }

        return ['message' => 'Pin user code does not exist.', 'exist' => false, 'code' => Response::HTTP_OK];
    }

    public function checkEmailIfExists($email): array
    {
        $user = $this->getUserRepository()->findByEmail($email, User::USER_TYPE_MEMBER, false);

        if ($user !== null) {
            return ['message' => 'Email exists.', 'code' => Response::HTTP_UNPROCESSABLE_ENTITY];
        }

        return ['message' => 'Email does not exist.', 'code' => Response::HTTP_OK];
    }

    public function checkEmailOrPhoneNumberIfExists($input): array
    {        
        $user = null;        
        if (isset($input['email']) && $input['email'] != '') {
            $user = $this->getUserRepository()->findUserByEmail($input['email']);
        } elseif (isset($input['phoneNumber']) && $input['phoneNumber'] != '') {
            $countryPhoneCode = isset($input['countryPhoneCode']) ? $input['countryPhoneCode'] : "";
            $user = $this->getUserRepository()->findUserByPhoneNumber($input['phoneNumber'], $countryPhoneCode);            
        }

        if ($user !== null) {
            return ['message' => 'User already exists.', 'exist' => 'true', 'code' => Response::HTTP_UNPROCESSABLE_ENTITY];
        }

        return ['message' => 'User does not exists.', 'exist' => 'false', 'code' => Response::HTTP_OK];
    }

    public function checkCredentialsIfExist($credentials): array
    {
        if (isset($credentials['phoneNumber']) && $credentials['phoneNumber']) {
            $countryPhoneCode = isset($credentials['countryPhoneCode']) ? $credentials['countryPhoneCode'] : "";
            $user = $this->getUserRepository()->findUserByPhoneNumber($credentials['phoneNumber'], $countryPhoneCode);
        } else if (isset($credentials['email']) && $credentials['email']) {
            $user = $this->getUserRepository()->findUserByEmail($credentials['email']);
        } else {
            $user = new User();
        }

        if ($user !== null) {
            $customer = $user->getCustomer();
            $customer_id = $customer->getId();
            
            if (isset($credentials['password'])) {
                $checkPassword = $this->passwordIsValid($customer, $credentials['password']);
                
                // get paymentOptions                                
                $query = 'select c.customer_payment_option_id as id, c.customer_payment_options_customer_id as cid, c.customer_payment_option_type as type
                            from customer_payment_option c
                            where c.customer_payment_options_customer_id = '.$customer_id.'
                            and c.customer_payment_option_is_active = 1
                            order by c.customer_payment_option_created_at desc';
                $em = $this->getDoctrine()->getManager();
                $qu = $em->getConnection()->prepare($query);
                $qu->execute();
                $res = $qu->fetchAll();
                $customerPaymentOptions = [];
                foreach($res as $key => $val){
                    $customerPaymentOptions[strtolower($val['type'])][] = $val;
                }
                
                // get configs
                // array
                $configs = $this->getSettingRepository()->getSettingByCodes(array("piwi247.session", "transaction.validate"));
//                $piwi247Session = $this->getSettingManager()->getSetting('piwi247.session', []);
                if ($checkPassword) {
                    return [
                        'error' => 'false',
                        'data' => [
                            'userCode' => $customer->getPinUserCode(),
                            'loginId' => $customer->getPinLoginId(),
                            'balance' => $customer->getBalance(),
                            'fullName' => $customer->getFullName(),
                            'isVerified' => $customer->isVerified(),
                            'customerId' => $customer_id,
                            'paymentOptions' => $customerPaymentOptions,
                            'configs' => $configs
                        ],
                        'message' => ''
                    ];
                }
            }
        }

        return [
            'error' => 'true',
            'message' => 'The Phone/Email or password is incorrect. Please try again.'
        ];
    }

//    public function chec

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
    
    // zimi
    public function handleForgotPassword($data)
    {            
        # via phone        
        $user_repo = $this->getUserRepository();
        if ($data['viaType'] == 0) {            
            $user = $user_repo->findUserByPhoneNumber($data['phoneNumber'], $data['phoneCode']);
        } else {            
            $user = $user_repo->findUserByEmail($data['email']);
        }

        $encoder = $this->getEncoderFactory()->getEncoder($user);
        $user->setPassword($encoder->encodePassword($data['password'], $user->getSalt()));
        $user->setResetPasswordCode(null);
        $user->setResetPasswordSentTimestamp(null);

        try {
            return $this->getUserManager()->save($user);
        } catch (\Exception $e) {
            return $e;
        }

        return false;
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

    private function getCustomerPaymentOptionRepository(): \DbBundle\Repository\CustomerPaymentOptionRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerPaymentOption');
    }

    // zimi
    private function getSettingManager(): \AppBundle\Manager\SettingManager
    {
        return $this->getContainer()->get('app.setting_manager');
    }
    
    private function getSettingRepository(): \DbBundle\Repository\SettingRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Setting');
    }
}
