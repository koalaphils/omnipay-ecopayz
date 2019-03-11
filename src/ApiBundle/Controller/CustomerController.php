<?php

namespace ApiBundle\Controller;

use DbBundle\Entity\Transaction;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Request\ParamFetcher;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Exceptions\FormValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use DbBundle\Entity\Country;

class CustomerController extends AbstractController
{
    /**
     * @ApiDoc(
     *  description="Get Details of current user",
     *  statusCodes={
     *      200="Returned customer exists",
     *      401="Returned when the user is not authorized",
     *      404="Returned if customer not exists"
     *  },
     *  filters={
     *     {
     *          "name"="include",
     *          "dataType"="string"
     *     }
     *   },
     *  responseMap={
     *      200={
     *          "class"="DbBundle\Entity\Customer",
     *          "parsers"={ "ApiBundle\Parser\JmsMetadataParser" },
     *          "groups"={ "API" }
     *      }
     *  }
     * )
     */
    public function meAction(Request $request)
    {
        $user = $this->getUser();
        if ($user->getCustomer() === null) {
            throw $this->createNotFoundException('Customer not found');
        }

        /* @var $user \DbBundle\Entity\User */
        $view = $this->view($user->getCustomer());
        $groups = array_merge($view->getContext()->getGroups(), explode(',', $request->get('include')));

        if (in_array('preferences', $groups)) {
            $transactionStatus = $this->getSettingManager()->getSetting('transaction.status');
            $preferencesExtra = ['transaction' => ['statuses' => [], 'statusGroups' => []]];
            foreach ($transactionStatus as $key => $status) {
                $label = array_get($status, 'amsLabel', array_get($status, 'label'));
                $preferencesExtra['transaction']['statuses'][$key] = $label;
                if (!isset($preferencesExtra['transaction']['statusGroups'][$label])) {
                    $preferencesExtra['transaction']['statusGroups'][$label] = [];
                }
                $preferencesExtra['transaction']['statusGroups'][$label][] = $key;
            }

            $otherStatuses = Transaction::getOtherStatus();
            foreach ($otherStatuses as $otherStatus) {
                $preferencesExtra['transaction']['statuses'][$otherStatus] = $this->getTranslator()->trans($otherStatus, [], 'TransactionBundle');
            }

            $user->getCustomer()->setExtraPreferences($preferencesExtra);
        }

        $view->getContext()->setGroups($groups);

        return $view;
    }

    /**
     * @ApiDoc(
     *  description="Get customer preferences",
     *  statusCodes={
     *      200="Returned successful",
     *      401="Returned when the user is not authorized",
     *      404="Returned if customer not exists"
     *  }
     * )
     */
    public function mePreferencesAction()
    {
        /* @var $user \DbBundle\Entity\User */
        $user = $this->getUser();

        if ($user->getCustomer() === null) {
            throw $this->createNotFoundException('Customer not found');
        }

        $transactionStatus = $this->getSettingManager()->getSetting('transaction.status');
        $preferencesExtra = ['transaction' => ['statuses' => [], 'statusGroups' => []]];
        foreach ($transactionStatus as $key => $status) {
            $label = array_get($status, 'amsLabel', array_get($status, 'label'));
            $preferencesExtra['transaction']['statuses'][$key] = $label;
            if (!isset($preferencesExtra['transaction']['statusGroups'][$label])) {
                $preferencesExtra['transaction']['statusGroups'][$label] = [];
            }
            $preferencesExtra['transaction']['statusGroups'][$label][] = $key;
        }
        $user->getCustomer()->setExtraPreferences($preferencesExtra);

        return $this->view($user->getCustomer()->getPreferences());
    }

    /**
     * @ApiDoc(
     *  description="Get customer notifications",
     *  statusCodes={
     *      200="Returned successful",
     *      401="Returned when the user is not authorized",
     *  }
     * )
     */
    public function notificationsAction()
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createNotFoundException('Customer not found');
        }

        return $this->view($user->getCustomer()->getNotifications());
    }

    public function readNotificationsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        if (!$user) {
            throw $this->createNotFoundException('Customer not found');
        }

        $user->getCustomer()->readNotifications();
        $em->flush();

        return $this->view([]);
    }

    /**
     * @ApiDoc(
     *  description="Get current customer products",
     *  output={
     *      "class"="ArrayCollection<DbBundle\Entity\CustomerProduct>",
     *      "parsers"={ "ApiBundle\Parser\CollectionParser", "ApiBundle\Parser\JmsMetadataParser" },
     *      "groups"={ "API" }
     *  }
     * )
     */
    public function customerProductsAction()
    {
        $user = $this->getUser();
        if ($user->getCustomer() === null) {
            throw $this->createNotFoundException('Customer not found');
        }
        $customerProducts = $user->getCustomer()->getProducts();
        $total = $customerProducts->count();
        $collection = new \DbBundle\Collection\Collection($customerProducts, $total, $total, $total, 1);

        return $this->view($collection);
    }

    /**
     * @ApiDoc(
     *  description="Get customer product",
     *  statusCodes={
     *      200="Returned customer product exists",
     *      401="Returned when the user is not authorized",
     *      404="Returned if customer product not exists"
     *  },
     *  responseMap={
     *      200={
     *          "class"="DbBundle\Entity\CustomerProduct",
     *          "parsers"={ "ApiBundle\Parser\JmsMetadataParser" },
     *          "groups"={ "API" }
     *      }
     *  }
     * )
     */
    public function customerProductAction($id)
    {
        $customerProduct = $this->getCustomerProductRepository()->findByidAndCustomer(
            $id,
            $this->getUser()->getCustomer()->getId()
        );
        if ($customerProduct === null) {
            throw $this->createNotFoundException('Customer not found');
        }

        return $this->view($customerProduct);
    }

    /**
     * @ApiDoc(
     *  description="Get customer payment options",
     *  output={
     *      "class"="ArrayCollection<DbBundle\Entity\CustomerPaymentOption>",
     *      "parsers"={ "ApiBundle\Parser\CollectionParser", "ApiBundle\Parser\JmsMetadataParser" },
     *      "groups"={ "API" }
     *  }
     * )
     */
    public function customerPaymentOptionsAction()
    {
        $user = $this->getUser();
        if ($user->getCustomer() === null) {
            throw $this->createNotFoundException('Customer not found');
        }
        $customerPaymentOptions = $user->getCustomer()->getPaymentOptions();
        $total = $customerPaymentOptions->count();
        $collection = new \DbBundle\Collection\Collection($customerPaymentOptions, $total, $total, $total, 1);

        return $this->view($collection);
    }

    /**
     * @ApiDoc(
     *  description="Get customer payment option",
     *  output={
     *      "class"="DbBundle\Entity\CustomerPaymentOption",
     *      "parsers"={ "ApiBundle\Parser\CollectionParser", "ApiBundle\Parser\JmsMetadataParser" },
     *      "groups"={ "API" }
     *  }
     * )
     */
    public function customerPaymentOptionAction($id)
    {
        $customerPaymentOption = $this->getCustomerPaymentOptionRepository()->findByidAndCustomer(
            $id,
            $this->getUser()->getCustomer()->getId()
        );
        if ($customerPaymentOption === null) {
            throw $this->createNotFoundException('Customer Payment Option not found');
        }

        return $this->view($customerPaymentOption);
    }

    /**
     * @ApiDoc(
     *  description="Validate activation code",
     *  requirements={
     *      {
     *          "name"="activationCode",
     *          "dataType"="string",
     *          "description"="Activation code sent to the user upon registration"
     *      }
     *  }
     * )
     */
    public function validateActivationCodeAction(Request $request)
    {
        $activationCode = $request->request->get('activationCode');

        $result = $this->getCustomerManager()->validateActivationCode($activationCode);

        return $this->view($result, $result['code']);
    }

    /**
     * @ApiDoc(
     *  description="Activate the user account after registration",
     *  input="ApiBundle\Form\Customer\AccountActivationType",
     *  requirements={
     *      {
     *          "name"="activationCode",
     *          "dataType"="string",
     *          "description"="Activation code sent to the user upon registration"
     *      }
     *  }
     * )
     */
    public function activateAccountAction(Request $request)
    {
        $activationCode = $request->request->get('activationCode');
        $activation = $request->request->get('activation');

        $form = $this->createForm(\ApiBundle\Form\Customer\AccountActivationType::class);
        $form->submit($activation);

        if ($form->isSubmitted() && $form->isValid()) {
            $accountActivationModel = $form->getData();
            $result = $this->getCustomerManager()->activateAccount($activationCode, $accountActivationModel);

            return $this->view($result, $result['code']);
        }

        return $this->view($form, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @ApiDoc(
     *  description="Check if the email input on registration exists or not",
     *  requirements={
     *      {
     *          "name"="email",
     *          "dataType"="string",
     *          "description"="Email input on registration form"
     *      }
     *  }
     * )
     */
    public function checkEmailIfExistsAction(Request $request)
    {
        $email = $request->request->get('email');

        $result = $this->getCustomerManager()->checkEmailIfExists($email);

        return $this->view($result, $result['code']);
    }

    /**
     * @ApiDoc(
     *  description="Check if pin user code input on registration exists or not",
     *  requirements={
     *      {
     *          "name"="pin_user_code",
     *          "dataType"="string",
     *          "description"="user code input on registration form"
     *      }
     *  }
     * )
     */
    public function checkPinUserCodeIfExistsAction(Request $request)
    {
        $pinUserCode = $request->request->get('pinUserCode');

        $result = $this->getCustomerManager()->checkPinUserCodeIfExists($pinUserCode);

        return $this->view($result, $result['code']);
    }

    /**
     * @ApiDoc(
     *  description="Check if email or phone Number on registration exists or not",
     *     requirements={
     *      {
     *          "name"="email,phone",
     *          "dataType"="string",
     *          "description"="user code input on registration form"
     *      }
     *  }
     * )
     */
    public function checkPhoneOrEmailIfExistsAction(Request $request)
    {
        $input = $request->request->all();

        $result = $this->getCustomerManager()->checkEmailOrPhoneNumberIfExists($input);

        return $this->view($result, $result['code']);
    }

    /**
     * @ApiDoc(
     *  description="Check if the username input on registration exists or not",
     *  requirements={
     *      {
     *          "name"="username",
     *          "dataType"="string",
     *          "description"="Username input on registration form"
     *      }
     *  }
     * )
     */
    public function checkUsernameIfExistsAction(Request $request)
    {
        $username = $request->request->get('username');

        $result = $this->getCustomerManager()->checkUsernameIfExists($username);

        return $this->view($result, $result['code']);
    }
    
    /**
     * @ApiDoc(
     *  description="Check if the credentials input on registration exists or not"
     * )
     */
    public function checkCredentialsIfExistsAction(Request $request)
    {
        $input = $request->request->all();
        $result = $this->getCustomerManager()->checkCredentialsIfExist($input);


        return $this->view($result);
    }

    /**
    * @ApiDoc(
    *  description="Register customer",
    *  input="ApiBundle\Form\Customer\RegisterType"
    * )
    */
    public function customerRegisterAction(Request $request)
    {                
        $registeredCustomer = $request->request->get('register');    
    
        // zimi                
        // $full_phone = $registeredCustomer['countryPhoneCode'] . substr($registeredCustomer['phoneNumber'], 1);
        $full_phone = ltrim($registeredCustomer['countryPhoneCode'], "+") . $registeredCustomer['phoneNumber'];

        if ($registeredCustomer['signupType'] == 0) {
            $query = 'SELECT sms_code_value FROM piwi_system_log_sms_code WHERE sms_code_customer_phone_number = \''.$full_phone.'\' order by sms_code_created_at desc limit 1';
        } else {
            $query = 'SELECT sms_code_value FROM piwi_system_log_sms_code WHERE sms_code_customer_email = \'' . $registeredCustomer['email'] . '\' order by sms_code_created_at desc limit 1';
        }                
        
        $em = $this->getDoctrine()->getManager();
        $qu = $em->getConnection()->prepare($query);
        $qu->execute();
        $res = $qu->fetchAll();
        
        // zimi
        if (count($res) > 0) {
            $res = $res[0];           
        } else {
            echo json_encode(['error' => true, 'status'=> 400, 'message' => 'SMS Code is invalid']);exit();
        }

        $sms_code_registerd = $registeredCustomer['smsCode'];
        $sms_code = $res['sms_code_value'];
        if ($sms_code_registerd != $sms_code ) {
            echo json_encode(['error' => true, 'status'=> 400, 'message' => 'SMS Code is invalid']);exit();
        }
        
        $originUrl = $request->headers->get('Origin');
        $referrerUrl = $request->headers->get('Referrer');
        $locale = $request->attributes->get('_locale');
        $ipAddress = $request->getClientIp();
        $form = $this->createForm(\ApiBundle\Form\Customer\RegisterType::class, null, ['allow_extra_fields' => true,]);
        $form->submit($registeredCustomer);

        // zimi-comment && $form->isValid()
        if ($form->isSubmitted()) {
            $registerModel = $form->getData();
            $registerModel->setSignupType($registeredCustomer['signupType']);            
            $customer = $this->getCustomerManager()->handleRegister($registerModel, $originUrl, $locale, $ipAddress, $referrerUrl);                  
            return $this->view($customer, Response::HTTP_OK);
        }

        return $this->view($form, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @ApiDoc(
     *  description="customer forgot password"     
     * )
     */    
    public function forgotPasswordAction(Request $request)
    {                
        $post = $request->request->all();                    
        $full_phone = ltrim($post['phoneCode'], "+") . $post['phoneNumber']; 

        // via phone - zimi
        if ($post['viaType'] == 0) {
            $query = 'SELECT sms_code_value FROM piwi_system_log_sms_code WHERE sms_code_customer_phone_number = \''.$full_phone.'\' order by sms_code_created_at desc limit 1';
        } else {
            $query = 'SELECT sms_code_value FROM piwi_system_log_sms_code WHERE sms_code_customer_email = \'' . $post['email'] . '\' order by sms_code_created_at desc limit 1';
        }

        $em = $this->getDoctrine()->getManager();
        $qu = $em->getConnection()->prepare($query);
        $qu->execute();
        $res = $qu->fetchAll();
        if (count($res) > 0) {
            $res = $res[0];           
        } else {
            echo json_encode(['error' => true, 'status'=> 200, 'message' => 'SMS Code is invalid']);exit();
        }

        $sms_code_registerd = $post['smsCode'];
        $sms_code = $res['sms_code_value'];
        if ($sms_code_registerd != $sms_code ) {
            echo json_encode(['error' => true, 'status'=> 200, 'message' => 'SMS Code is invalid']);exit();
        }
        
        // update passowrd
        $data = $post;        
        $result = $this->getCustomerManager()->handleForgotPassword($data);

        // update success
        if ($result == null) {                        
            echo json_encode(['error' => false, 'status'=> 200, 'message' => '']);exit();   
        }
                
        echo json_encode(['error' => true, 'status'=> 200, 'message' => 'Failure']);exit();           
    }

    /**
     * @ApiDoc(
     *  description="customer update password"     
     * )
     */    
    public function updatePasswordAction(Request $request)
    {                
        $post = $request->request->all();         
        $full_phone = ltrim($post['phoneCode'], "+") . $post['phoneNumber']; 
        $user_repo = $this->getUserRepository();

        // via phone - zimi
        if ($post['signupType'] == 0) {
            $query = 'SELECT sms_code_value FROM piwi_system_log_sms_code WHERE sms_code_customer_phone_number = \''.$full_phone.'\' order by sms_code_created_at desc limit 1';
            $user = $user_repo->findUserByPhoneNumber($post['phoneNumber'], $post['phoneCode']);
        } else {
            $query = 'SELECT sms_code_value FROM piwi_system_log_sms_code WHERE sms_code_customer_email = \'' . $post['email'] . '\' order by sms_code_created_at desc limit 1';
            $user = $user_repo->findUserByEmail($post['email']);
        }

        $em = $this->getDoctrine()->getManager();
        $qu = $em->getConnection()->prepare($query);
        $qu->execute();
        $res = $qu->fetchAll();
        if (count($res) > 0) {
            $res = $res[0];           
        } else {
            echo json_encode(['error' => true, 'status'=> 200, 'message' => 'SMS Code is invalid']);exit();
        }

        $sms_code_registerd = $post['smsCode'];
        $sms_code = $res['sms_code_value'];
        if ($sms_code_registerd != $sms_code ) {
            echo json_encode(['error' => true, 'status'=> 200, 'message' => 'SMS Code is invalid']);exit();
        }
        
        // validate password        
        $isValid = $this->getCustomerManager()->passwordIsValidByUser($user, $post['currentPassword']);
        if ($isValid == false ) {
            echo json_encode(['error' => true, 'status'=> 200, 'message' => 'Current password is invalid']);exit();
        }        

        // update passowrd
        $data = $post;                
        $data['viaType'] = $post['signupType'];
        $result = $this->getCustomerManager()->handleForgotPassword($data);

        // update success
        if ($result == null) {                        
            echo json_encode(['error' => false, 'status'=> 200, 'message' => '']);exit();   
        }
                
        echo json_encode(['error' => true, 'status'=> 200, 'message' => 'Failure']);exit();           
    }

    /**
     * @ApiDoc(
     *  description="Customer registration v2",
     *  input={
     *      "class"="ApiBundle\Form\Customer\RegisterV2\RegisterType"
     *  }
     * )
     */
    public function customerRegisterV2Action(Request $request)
    {
        $memberRegistration = $request->request->get('register');
        $registrationDetails = [];
        if (array_key_exists('registrationDetails', $memberRegistration)) {
            $registrationDetails['registrationDetails'] = $memberRegistration['registrationDetails'];
            unset($memberRegistration['registrationDetails']);
        }

        $tempMemberRegistrationModel = new \ApiBundle\Model\RegisterV2\Register();
        $form = $this->createForm(\ApiBundle\Form\Customer\RegisterV2\RegisterType::class, $tempMemberRegistrationModel);

        $form->submit($memberRegistration);

        if ($form->isSubmitted() && $form->isValid()) {
            $memberRegistrationModel = $form->getData();
            $registrationDetails = array_merge($registrationDetails, [
                'origin' => $request->headers->get('Origin'),
                'referrer' => $request->headers->get('Referrer'),
                'locale' => $request->attributes->get('_locale'),
                'ip' => $request->getClientIp(),
            ]);

            $member = $this->getCustomerManager()->handleRegisterV2($memberRegistrationModel, $registrationDetails);
            $view = $this->view($member);
            $view->getContext()->setGroups(['credentials']);

            return $view;
        }

        return $this->view($form, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @ApiDoc(
     *  description="Save restore Id generated by FreshChat Widget",
     *  requirements={
     *      {
     *          "name"="restoreId",
     *          "dataType"="string",
     *          "description"="RestoreId generated in AMS by FreshChat"
     *      }
     *  }
     * )
     */
    public function saveRestoreIdAction(Request $request): View
    {
        $restoreId = $request->request->get('restoreId');

        $result = $this->getCustomerManager()->handleRestoreId($restoreId);

        return $this->view($result, $result['code']);
    }

    /**
     * @ApiDoc(
     *  description="Update the customer username, password or transaction password",
     *  input="ApiBundle\Form\Customer\SecurityType"
     * )
     */
    public function updateSecurityAction(Request $request)
    {
        $security = $request->request->get('security', []);

        $securityModel = new \ApiBundle\Model\Security();
        $form = $this->createForm(\ApiBundle\Form\Customer\SecurityType::class, $securityModel);
        $form->submit($security);

        if ($form->isSubmitted() && $form->isValid()) {
            $securityModel = $form->getData();

            $security = $this->getCustomerManager()->handleSecurity($securityModel);

            return $this->view($security);
        }

        return $this->view($form, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @ApiDoc(
     *  description="Sends an email that contains reset password code of the user",
     *  input="ApiBundle\Form\Customer\RequestResetPasswordType"
     * )
     */
    public function requestResetPasswordAction(Request $request)
    {
        $requestResetPassword = $request->request->get('requestResetPassword', []);

        $requestResetPasswordModel = new \ApiBundle\Model\RequestResetPassword();
        $form = $this->createForm(\ApiBundle\Form\Customer\RequestResetPasswordType::class, $requestResetPasswordModel);
        $form->submit($requestResetPassword);
        $origin = $request->headers->get('Origin') ?: $this->getParameter('asianconnect_url');

        if ($form->isSubmitted() && $form->isValid()) {
            $requestResetPasswordModel = $form->getData();
            $requestResetPassword = $this->getCustomerManager()->handleRequestResetPassword($requestResetPasswordModel, $origin);

            return $this->view($requestResetPassword);
        }

        return $this->view($form, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @ApiDoc(
     *  description="Update the customer password using the reset code",
     *  input="ApiBundle\Form\Customer\ResetPasswordType",
     *  requirements={
     *      {
     *          "name"="resetPasswordCode",
     *          "dataType"="string",
     *          "description"="Reset password code to sent to the customer"
     *      },
     *     {
     *          "name"="email",
     *          "dataType"="string",
     *          "description"="Email of the customer"
     *      }
     *  }
     * )
     */
    public function resetPasswordAction(Request $request)
    {
        $resetPasswordCode = $request->request->get('resetPasswordCode');
        $email = $request->request->get('email');
        $resetPassword = $request->request->get('resetPassword', []);

        $resetPasswordModel = new \ApiBundle\Model\ResetPassword();
        $form = $this->createForm(\ApiBundle\Form\Customer\ResetPasswordType::class, $resetPasswordModel);
        $form->submit($resetPassword);

        if ($form->isSubmitted() && $form->isValid()) {
            $resetPasswordModel = $form->getData();

            $result = $this->getCustomerManager()->handleResetPassword($resetPasswordCode, $email, $resetPasswordModel);

            return $this->view($result, $result['code']);
        }

        return $this->view($form, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @ApiDoc(
     *  description="Validate reset password code",
     *  requirements={
     *      {
     *          "name"="resetPasswordCode",
     *          "dataType"="string",
     *          "description"="Reset password code sent to the user upon request"
     *      },
     *     {
     *
     *          "name"="email",
     *          "dataType"="string",
     *          "description"="Email of the customer"
     *      }
     *  }
     * )
     */
    public function validateResetPasswordCodeAction(Request $request)
    {
        $resetPasswordCode = $request->request->get('resetPasswordCode');
        $email = $request->request->get('email');

        $result = $this->getCustomerManager()->validateResetPasswordCode($resetPasswordCode, $email);

        return $this->view($result, $result['code']);
    }

    /**
     * @ApiDoc(
     *  description="Add in audit log the login and logout of customer",
     *  requirements={
     *      {
     *          "name"="category",
     *          "dataType"="string",
     *          "description"="login or logout action of the customer"
     *      }
     *  }
     * )
     */
    public function meAuditAction($category)
    {
        $result = $this->getCustomerManager()->handleAudit($category);

        return $this->view($result, $result['code']);
    }

    private function getCustomerRepository(): \DbBundle\Repository\CustomerRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Customer');
    }

    private function getUserRepository(): \DbBundle\Repository\UserRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:User');
    }

    private function getCustomerPaymentOptionRepository(): \DbBundle\Repository\CustomerPaymentOptionRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerPaymentOption');
    }

    private function getCustomerProductRepository(): \DbBundle\Repository\CustomerProductRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerProduct');
    }

    private function getCustomerManager(): \ApiBundle\Manager\CustomerManager
    {
        return $this->container->get('api.customer_manager');
    }

    private function getSettingManager(): \AppBundle\Manager\SettingManager
    {
        return $this->container->get('app.setting_manager');
    }
}
