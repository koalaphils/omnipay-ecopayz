<?php

namespace CustomerBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use CustomerBundle\Form\CustomerType;
use CustomerBundle\Form\CustomerProductType;
use CustomerBundle\Form\SecurityType;
use CustomerBundle\Form\BankType;
use CustomerBundle\Form\ContactsType;
use CustomerBundle\Form\SocialsType;
use CustomerBundle\Form\PaymentType;
use CustomerBundle\Form\KycType;
use DbBundle\Entity\Customer;
use DbBundle\Entity\Setting;
use DbBundle\Entity\User;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use AppBundle\Exceptions\FormValidationException;
use DbBundle\DataTransfer\CustomerDataTransfer;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;

class CustomerController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_VIEW']);
        $this->getSettingRepository()->updateSetting('customer', 'counter', 0);

        return $this->render('CustomerBundle:Customer:index.html.twig');
    }

    /**
     *
     * @deprecated since version 1.1
     *
     * @param Request $request
     *
     * @return Response
     */
    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_VIEW']);
        $this->getSession()->save();
        $filters = $results = [];
        $filters = $request->request->all();
        $filters = array_merge($filters, $request->query->all());
        $results = $this->getManager()->getCustomerList($filters);

        return new JsonResponse($results, JsonResponse::HTTP_OK);
    }

    public function listAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_VIEW']);
        $this->getSession()->save();
        $options = $request->request->all();
        $options = array_merge($options, $request->query->all());
        $results = $this->getManager()->getList($options);

        if ($request->get('datatable')) {
            $results['draw'] = $request->get('draw');
        }

        return $this->response($request, $results, ['groups' => ['Default', 'groups', 'affiliate']]);
    }

    public function createPageAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_CREATE']);
        $status = true;
        $form = $this->createForm(CustomerType::class, null, [
            'action' => $this->getRouter()->generate('customer.create'),
            'guestType' => Customer::CUSTOMER,
        ]);
        $form->handleRequest($request);

        return $this->render('CustomerBundle:Customer:create.html.twig', ['form' => $form->createView()]);
    }

    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_CREATE']);
        $customer = new Customer();
        $guestType = array_get($request->request->get('Customer'), 'guestType');
        $validationGroups = ['default', 'withPassword'];
        if ($guestType != Customer::AFFILIATE) {
            $validationGroups[] = 'customer';
        }
        if (is_null($guestType)) {
            throw new \Doctrine\ORM\NoResultException();
        }
        $form = $this->createForm(CustomerType::class, $customer, ['validation_groups' => $validationGroups, 'guestType' => $guestType]);
        $response = ['success' => false];
        try {
            $customer = $this->getManager()->handleCreateCustomerForm($form, $request);
            $response['success'] = true;
            $response['data'] = $customer;
            if ($guestType == Customer::AFFILIATE) {
                $response['link'] = $this->getRouter()->generate('affiliate.update_page', ['id' => $customer->getId()]) . '/profile';
            } else {
                $response['link'] = $this->getRouter()->generate('customer.update_page', ['id' => $customer->getId()]) . '/profile';
            }
        } catch (FormValidationException $e) {
            $response['errors'] = $e->getErrors();
        }

        return new Response($this->serialize($response, [
            'groups' => ['Default', 'affiliate', 'groups'],
        ]));
    }

    /**
     * @deprecated since version 1.1
     *
     * @param Request $request
     * @param mixed   $id
     *
     * @return type
     */
    public function saveAction(Request $request, $id = 'new')
    {
        $status = true;
        $validationGroups = ['default', 'customer'];
        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_CREATE']);
            $customer = new Customer();
        } else {
            $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_UPDATE']);
            /* @var $customer \DbBundle\Entity\Customer */
            $customer = $this->getManager()->findById($id);
            $this->getEntityManager()->initializeObject($customer->getGroups());
        }

        if ('new' === $id) {
            $validationGroups[] = 'withPassword';
        }

        $form = $this->createForm(CustomerType::class, $customer, [
            'validation_groups' => $validationGroups,
            'guestType' => Customer::CUSTOMER,
        ]);


        if ('new' !== $id) {
            $form->get('user')->remove('changePassword')->remove('password');
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $customer = $form->getData();

            if ('new' === $id || array_get($request->request->get('Customer'), 'user.changePassword', 0)) {
                $password = $this
                    ->get('security.password_encoder')
                    ->encodePassword($customer->getUser(), $customer->getUser()->getPassword())
                ;
                $customer->getUser()->setPassword($password);
                $transactionPassword = $this->get('security.password_encoder')->encodePassword($customer->getUser(), '');
                $customer->setTransactionPassword($transactionPassword);
            }

            $formData = $request->request->get('Customer');
            $customer->getUser()->setPreference('affiliateCode', $formData['affiliateCode']);
            $customer->getUser()->setPreference('promoCode', $formData['promoCode']);

            $this->getCustomerRepository()->save($customer);

            $this->getSession()->getFlashBag()->add('notifications', [
                'title' => $this->getTranslator()->trans(
                    'notification.title',
                    ['%fName%' => $customer->getfName(), '%lName%' => $customer->getlName()],
                    'CustomerBundle'
                ),
                'message' => $this->getTranslator()->trans(
                    'notification.' . ($id === 'new' ? 'created' : 'updated'),
                    ['%fName%' => $customer->getfName(), '%lName%' => $customer->getlName()],
                    'CustomerBundle'
                ),
            ]);
            $redirectTo = $this->getRouter()->generate('customer.update_page', ['id' => $customer->getId()]) . '/profile';

            return $this->redirect($redirectTo, 301);
        }

        $this->getSession()->getFlashBag()->add('notifications', [
            'title' => $this->getTranslator()->trans('notification.error.title', [], 'CustomerBundle'),
            'message' => $this->getTranslator()->trans('notification.error.message', [], 'CustomerBundle'),
            'type' => 'error'
        ]);

        return $this->redirect($request->headers->get('referer'), 307);
    }

    public function convertAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_CONVERT_TO_AFFILIATE']);
        if (!$request->isXmlHttpRequest() && $request->get('callback', false) === false) {
            throw new \RuntimeException('Callback is undefined', JsonResponse::HTTP_EXPECTATION_FAILED);
        }

        /* @var $customer \DbBundle\Entity\Customer */
        $customer = $this->getManager()->findById($id);
        if ($customer) {
            if ($customer->getIsCustomer()) {
                $notification = [
                    'type' => 'error',
                    'title' => $this->getTranslator()->trans('notification.convertCustomer.error.title', [], 'CustomerBundle'),
                    'message' => $this->getTranslator()->trans('notification.convertCustomer.error.message', [], 'CustomerBundle'),
                ];
            } else {
                $customer->setIsCustomer(true);
                $this->getManager()->save($customer);
                $notification = [
                    'type' => 'success',
                    'title' => $this->getTranslator()->trans('notification.convertCustomer.success.title', [], 'CustomerBundle'),
                    'message' => $this->getTranslator()->trans('notification.convertCustomer.success.message', [], 'CustomerBundle'),
                ];
            }
        } else {
            throw $this->createNotFoundException('Customer Not Found');
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['__notifications' => $notification, JsonResponse::HTTP_OK]);
        }
        $this->getSession()->getFlashBag()->add('notifications', $notification);

        return $this->redirect($request->get('callback'));
    }

    public function updateAction(Request $request, $id, $activeTab)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_UPDATE']);
        $status = true;
        $guestType = null;

        if (empty($id) || !preg_match('/^[0-9]*$/', $id)) {
            throw new \Doctrine\ORM\NoResultException();
        } else {
            $customer = $this->getManager()->findById($id);
        }

        if ($request->attributes->get('_route') == 'affiliate.update_page') {
            $guestType = Customer::AFFILIATE;
            $this->getMenuManager()->setActive('customer.affiliate');
        } else if ($request->attributes->get('_route') == 'customer.update_page') {
            $guestType = Customer::CUSTOMER;
            $this->getMenuManager()->setActive('customer.list');
        }

        $form = $this->createForm(CustomerType::class, $customer, [
            'action' => $this->getRouter()->generate('customer.save', ['id' => $id]),
            'guestType' => $guestType,
        ]);
        $form->get('user')->remove('changePassword')->remove('password');
        $form->handleRequest($request);

        $customerProductForm = $this->createForm(CustomerProductType::class, null, [
            'customerId' => $customer->getId(),
            'action' => $this->getRouter()->generate('customerProduct.save', ['id' => $customer->getId()]),
        ]);

        $customerSecurity = $this->createForm(SecurityType::class, $customer, [
            'action' => $this->getRouter()->generate('customer.security_save', ['id' => $id]),
        ]);

        $contactForm = $this->createForm(ContactsType::class, $customer, [
            'action' => $this->getRouter()->generate('customer.contact_save', ['id' => $id]),
        ]);

        $socialForm = $this->createForm(SocialsType::class, $customer, [
            'action' => $this->getRouter()->generate('customer.social_save', ['id' => $id]),
        ]);

        $paymentForm = $this->createForm(PaymentType::class, null, [
            'action' => $this->getRouter()->generate('customer.payment_option_save', ['id' => $id]),
        ]);

        $kyc = $this->createForm(kycType::class, null, [
            'guestType' => $guestType,
            'action' => $this->getRouter()->generate('customer.verify', ['id' => $id]),
        ]);

        $paymentOptions = $this->getRepository(\DbBundle\Entity\PaymentOption::class)->filter([], [], null, null, \Doctrine\ORM\Query::HYDRATE_ARRAY);
        $_paymentOptions = [];
        foreach ($paymentOptions as $paymentOption) {
            $_paymentOptions[$paymentOption['code']] = $paymentOption;
            $_paymentOptions[$paymentOption['code']]['fields'] = [];
            foreach (($paymentOption['fields'] ?? []) as $field) {
                $_paymentOptions[$paymentOption['code']]['fields'][$field['code']] = $field;
            }
        }
        unset($paymentOptions);

        return $this->render('CustomerBundle:Customer:update.html.twig', [
            'form' => $form->createView(),
            'customerProductForm' => $customerProductForm->createView(),
            'customer' => $customer,
            'securityForm' => $customerSecurity->createView(),
            //'bankForm'              => $bankForm->createView(),
            'contactForm' => $contactForm->createView(),
            'socialForm' => $socialForm->createView(),
            'paymentForm' => $paymentForm->createView(),
            'paymentOptions' => $_paymentOptions,
            'kyc' => $kyc->createView(),
            'guestType' => $guestType,
            'activeTab' => $activeTab,
            'topTenIps' => $this->getManager()->getCustomerLoginHistory(['id' => $customer->getUser()->getId()]),
        ]);
    }

    public function securitySaveAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_UPDATE']);
        $customer = $this->getCustomerRepository()->find($id);

        $validationGroups = 'default';
        if (array_has($request->get('CustomerSecurity', []), 'savePassword')) {
            $validationGroups = 'customer';
        }
        if (array_has($request->get('CustomerSecurity', []), 'saveTransactionPassword')) {
            $validationGroups = 'withTransactionPassword';
        }

        $securityForm = $this->createForm(SecurityType::class, $customer, [
            'validation_groups' => $validationGroups,
            'password_mapped' => $validationGroups === 'customer',
            'transaction_mapped' => $validationGroups === 'withTransactionPassword',
        ]);

        $securityForm->handleRequest($request);

        if ($securityForm->isSubmitted() && $securityForm->isValid()) {
            $data = $securityForm->getData();
            $btn = $securityForm->getClickedButton();

            if ($btn->getName() === 'savePassword') {
                $password = $this->get('security.password_encoder')->encodePassword(
                    $data->getUser(),
                    $data->getUser()->getPassword()
                );
                $data->getUser()->setPassword($password);
                $this->getCustomerRepository()->save($data);
            } elseif ($btn->getName() === 'saveTransactionPassword') {
                $password = $this->get('security.password_encoder')->encodePassword(
                    $data->getUser(),
                    $data->getTransactionPassword()
                );
                $data->setTransactionPassword($password);
                $this->getManager()->save($data);
            }

            return new JsonResponse(
                [
                    '__notifications' => [[
                        'type' => 'success',
                        'title' => $this->getTranslator()->trans(
                            'notification.title',
                            ['%fName%' => $customer->getfName(), '%lName%' => $customer->getlName()],
                            'CustomerBundle'
                        ),
                        'message' => $this->getTranslator()->trans(
                            'notification.' . ($id === 'new' ? 'created' : 'updated'),
                            ['%fName%' => $customer->getfName(), '%lName%' => $customer->getlName()],
                            'CustomerBundle'
                        ),
                    ], ],
                    'action' => $btn->getName(),
                ],
                200
            );
        }

        $errors = $this->getManager()->getErrorMessages($securityForm);
        $errors = ['errors' => array_dot($errors, '_', $securityForm->getName() . '_', true)];

        return new JsonResponse($errors, 422);
    }

    public function bankSaveAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_UPDATE']);
        $customer = $this->getCustomerRepository()->find($id);

        if (!$customer) {
            throw new \Doctrine\ORM\NoResultException();
        }
        $bankForm = $this->createForm(BankType::class, $customer, [
            'validation_groups' => 'bank',
        ]);

        $bankForm->handleRequest($request);

        if ($bankForm->isSubmitted() && $bankForm->isValid()) {
            $data = $bankForm->getData();
            $this->getManager()->save($data);

            return new JsonResponse([
                '__notifications' => [[
                    'type' => 'success',
                    'title' => $this->getTranslator()->trans('notification.bank.title', [], 'CustomerBundle'),
                    'message' => $this->getTranslator()->trans(
                        'notification.bank.message',
                        ['%name%' => $customer->getFName() . ' ' . $customer->getLName() . '(' . $customer->getUser()->getUsername() . ')'],
                        'CustomerBundle'
                    ),
                ], ],
            ], 200);
        }
    }

    public function verifyAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_UPDATE']);
        $status = (bool) $request->query->get('_status');
        $customer = $this->getManager()->findById($id);
        $label = $status ? 'verified' : 'unverified';
        $message = [
            'type' => 'success',
            'title' => $this->getTranslator()->trans('notification.verified.title.' . $label, [], 'CustomerBundle'),
            'message' => $this->getTranslator()->trans(
                'notification.verified.message.' . $label,
                [
                    '%name%' => $customer->getFName() . ' ' . $customer->getLName() . '(' . $customer->getUser()->getUsername() . ')'
                ],
                'CustomerBundle'
            ),
        ];
        if (!$customer) {
            throw new \Doctrine\ORM\NoResultException();
        }

        if ($status) {
            $customer->setVerifiedAt(new \DateTime());
        } else {
            $customer->setVerifiedAt(null);
        }
        $this->getCustomerRepository()->save($customer);

        if (!$request->isXmlHttpRequest()) {
            $this->getSession()->getFlashBag()->add('notifications', $message);

            return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
        }

        return new JsonResponse(
            ['__notifications' => [ $message, ], ],
            JsonResponse::HTTP_OK
        );
    }

    public function documentListAction($id)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_VIEW']);
        $files = $this->getManager()->getDocuments($id);

        return new JsonResponse($files);
    }

    public function saveDocumentAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_UPDATE']);
        $customer = $this->getCustomerRepository()->find($id);

        $info = $request->get('name', null);
        $value = $request->get('value', '');
        $index = $request->get('pk', null);

        if (!is_null($info) && !is_null($index)) {
            $customer->updateFile($index, $info, $value);
            $file = $customer->getFile($index);
            $path = array_get($file, 'folder', '') . '/' . $file['file'];
            $fileInfo = $this->getMediaManager()->getFile($path, true);
            $this->getCustomerRepository()->save($customer);

            return new JsonResponse([
                '__notifications' => [[
                    'type' => 'success',
                    'title' => $this->getTranslator()->trans(
                        'notification.document.update.success.title',
                        [],
                        'CustomerBundle'
                    ),
                    'message' => $this->getTranslator()->trans(
                        'notification.document.update.success.message',
                        ['%title%' => $file['title'], '%field%' => $info],
                        'CustomerBundle'
                    ),
                ], ],
                'index' => $index,
                'info' => $info,
                'value' => $value,
                'data' => $fileInfo,
            ], 200);
        }

        return new JsonResponse([
            '__notifications' => [[
                'type' => 'danger',
                'title' => $this->getTranslator()->trans(
                    'notification.document.update.error.title',
                    [],
                    'CustomerBundle'
                ),
                'message' => $this->getTranslator()->trans(
                    'notification.document.update.error.message',
                    ['%title%' => $file['title'], '%field%' => $info],
                    'CustomerBundle'
                ),
            ], ],
        ], 422);
    }

    public function uploadAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_UPDATE']);
        $this->getSession()->save();

        $customer = $this->getManager()->findById($id);
        $status = $this->getMediaManager()->uploadFile($request->files->get('file'), $request->get('folder', ''));
        if ($status['success']) {
            $customer->addFile([
                'folder' => $status['folder'],
                'file' => $status['filename'],
                'title' => $status['filename'],
                'description' => '',
            ]);

            $index = array_keys($customer->getFiles());
            rsort($index);
            $index = $index[0];

            $this->getCustomerRepository()->save($customer);

            $path = array_get($status, 'folder', '') . '/' . $status['filename'];
            $file = $this->getMediaManager()->getFile($path, true);
            $file['title'] = $status['filename'];
            $file['descript'] = '';
            $file['file'] = $status['filename'];
            $message = ['index' => $index, 'file' => $file];
        } else {
            $message = $status['error'];
            $status['code'] = 500;
        }

        return new JsonResponse($message, $status['code']);
    }

   public function deleteFileAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_UPDATE']);
        $this->getSession()->save();

        if (!is_null($request->get('index', null))) {
            $customer = $this->getManager()->findById($id);
            $file = $customer->getFile($request->get('index'));
            $customer->removeFile($request->get('index'));
            $this->getCustomerRepository()->save($customer);

            return new JsonResponse([
                '__notifications' => [[
                    'type' => 'success',
                    'title' => $this->getTranslator()->trans(
                        'notification.document.delete.success.title',
                        [],
                        'CustomerBundle'
                    ),
                    'message' => $this->getTranslator()->trans(
                        'notification.document.delete.success.message',
                        ['%title%' => $file['title']],
                        'CustomerBundle'
                    ),
                ], ],
                'index' => $request->get('index'),
            ]);
        }

        return new JsonResponse([
            '__notifications' => [
                'type' => 'danger',
                'title' => $this->getTranslator()->trans(
                    'notification.document.delete.error.title',
                    [],
                    'CustomerBundle'
                ),
                'message' => $this->getTranslator()->trans(
                    'notification.document.delete.error.message',
                    ['%title%' => $file['title']],
                    'CustomerBundle'
                ),
            ],
        ], 500);
    }

    public function saveContactAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_UPDATE']);
        $this->getSession()->save();
        $customer = $this->getManager()->findById($id);

        if ($customer) {
            $contactForm = $this->createForm(ContactsType::class, $customer, []);
            $contactForm->handleRequest($request);
            if ($contactForm->isSubmitted() && $contactForm->isValid()) {
                $data = $contactForm->getData();

                $this->getManager()->save($data);
                return new JsonResponse(['__notifications' => [
                        [
                            'type' => 'success',
                            'title' => $this->getTranslator()->trans('notification.contact.title', [], 'CustomerBundle'),
                            'message' => $this->getTranslator()->trans('notification.contact.message', [], 'CustomerBundle'),
                        ],
                    ],
                ], JsonResponse::HTTP_OK);
            }

            if (!$contactForm->isValid()) {
                return $this->getContactValidationErrorResponse($contactForm);
            }

        }
    }

    private function getContactValidationErrorResponse($contactForm)
    {
        $errorMessage = (String) $contactForm->getErrors(true, true);
        $errorMessage = ltrim($errorMessage, 'ERROR:');
        return new JsonResponse(['__notifications' => [
            [
                'type' => 'error',
                'title' => $this->getTranslator()->trans('notification.contact.error-title', [], 'CustomerBundle'),
                'message' => $errorMessage,
            ],
        ],
        ], JsonResponse::HTTP_OK); # HTTP_OK is used over HTTP_BAD_REQUEST to not trigger the profiler popup (dev env) for validation errors
    }

    public function readAction()
    {
        $status = false;
        $result = [];

        $status = $this->getUserRepository()->updateUserPreference($key = 'isRead');
        if ($status) {
            $result = [
                'status' => $status,
            ];
        }

        return new JsonResponse($result, $status ? JsonResponse::HTTP_OK : JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function saveSocialAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_UPDATE']);
        $this->getSession()->save();
        $customer = $this->getManager()->findById($id);

        if ($customer) {
            $socialForm = $this->createForm(SocialsType::class, $customer, []);
            $socialForm->handleRequest($request);

            if ($socialForm->isSubmitted() && $socialForm->isValid()) {
                $data = $socialForm->getData();

                $this->getManager()->save($data);

                return new JsonResponse(['__notifications' => [[
                        'type' => 'success',
                        'title' => $this->getTranslator()->trans('notification.social.title', [], 'CustomerBundle'),
                        'message' => $this->getTranslator()->trans('notification.social.message', [], 'CustomerBundle'),
                    ], ],
                ], 200);
            }
        }
    }

    public function savePaymentOptionAction(Request $request, $id, $index = 'new')
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_UPDATE']);
        $this->getSession()->save();
        $customer = $this->getManager()->findById($id);
        if ($customer) {
            $paymentForm = $this->createForm(PaymentType::class, [], []);
            $paymentForm->handleRequest($request);

            if ($paymentForm->isSubmitted() && $paymentForm->isValid()) {
                $data = $paymentForm->getData();
                $data = array_merge($data, $paymentForm->getExtraData());

                if ($index === 'new') {
                    $keys = array_keys($customer->getPaymentOptions());
                    if (!empty($keys)) {
                        rsort($keys);
                        $index = $keys[0] + 1;
                    } else {
                        $index = 0;
                    }
                    $customer->setPaymentOption($index, $data);
                } else {
                    $customer->setPaymentOption($index, $data);
                }

                $this->getManager()->save($customer);

                return new JsonResponse([
                    'index' => $index,
                    'paymentOption' => $data,
                    '__notifications' => [[
                        'type' => 'success',
                        'title' => $this->getTranslator()->trans(
                            'notification.paymentOption.save.success.title',
                            [],
                            'CustomerBundle'
                        ),
                        'message' => $this->getTranslator()->trans(
                            'notification.paymentOption.save.success.message',
                            [],
                            'CustomerBundle'
                        ),
                    ], ],
                ], 200);
            }
        }
    }

    public function deletePaymentOptionAction(Request $request, $id)
    {
        $this->getSession()->save();
        if (!is_null($request->get('index', null))) {
            $customer = $this->getManager()->findById($id);
            $customer->removePaymentOption($request->get('index'));
            $this->getCustomerRepository()->save($customer);

            return new JsonResponse([
                '__notifications' => [[
                    'type' => 'success',
                    'title' => $this->getTranslator()->trans(
                        'notification.paymentOption.delete.success.title',
                        [],
                        'CustomerBundle'
                    ),
                    'message' => $this->getTranslator()->trans(
                        'notification.paymentOption.delete.success.message',
                        [],
                        'CustomerBundle'
                    ),
                ], ],
                'index' => $request->get('index'),
            ]);
        }

        return new JsonResponse([
            '__notifications' => [
                'type' => 'danger',
                'title' => $this->getTranslator()->trans(
                    'notification.paymentOption.delete.error.title',
                    [],
                    'CustomerBundle'
                ),
                'message' => $this->getTranslator()->trans(
                    'notification.paymentOption.delete.error.message',
                    ['%title%' => $file['title']],
                    'CustomerBundle'
                ),
            ],
        ], 500);
    }

    public function resendActivationAction(Request $request, $id)
    {
        $user = $this->getUserRepository()->find($id);

        if (!$user) {
            throw new \Doctrine\ORM\NoResultException();
        }

        $customer = $user->getCustomer();
        $tempPassword = uniqid(str_replace(' ', '', 'temp') . '_');

        $user->setActivationCode(
            $this->getUserManager()->encodeActivationCode($user)
        );
        $user->setActivationSentTimestamp(new \DateTime());
        $user->setActivationTimestamp(new \DateTime());
        $user->setPassword(
            $this->getUserManager()->encodePassword($user, $tempPassword)
        );
        $user->setPlainPassword($tempPassword);
        $this->getUserRepository()->save($user);

        $activationCode = [
            'username' => $user->getUsername(),
            'password' => $tempPassword,
            'activation_code' => $user->getActivationCode(),
        ];

        $this->getUserManager()->sendActivationMail(
            [
                'username' => $user->getUsername(),
                'password' => $user->getPlainPassword(),
                'email' => $user->getEmail(),
                'fullName' => $customer->getFullName(),
                'originFrom' => $this->getParameter('asianconnect_url'),
                'activationCode' => JWT::encode($activationCode, 'AMSV2'),
                'isAffiliate' => $customer->isTagAsAffiliate(),
            ]
        );

        return new JsonResponse([
            '__notifications' => [
                [
                    'type' => 'success',
                    'title' => $this->getTranslator()->trans('notification.activation.success.title', [], 'CustomerBundle'),
                    'message' => $this->getTranslator()->trans(
                        'notification.activation.success.message',
                        ['%name%' => $customer->getFullName() . ' (' . $user->getUsername() . ')'],
                        'CustomerBundle'
                    ),
                ],
            ],
        ], Response::HTTP_OK);
    }

    public function activateCustomerAction(Request $request)
    {
        $user = $this->getUserRepository()->find($request->get('customerId'));

        if (!$user) {
            throw new \Doctrine\ORM\NoResultException();
        }

        $customer = $user->getCustomer();

        $passwords = [
            'password' => $request->get('password'),
            'transactionPassword' => $request->get('transactionPassword')
        ];

        if (!$user->isActivated()) {
            $this->activate($user, $customer, $passwords);
            if (!$customer->isTagAsAffiliate()) {
                $this->getMailer()
                    ->send(
                        $this->getTranslator()->trans('email.subject.activation', [], "AppBundle"),
                        $user->getEmail(),
                        'activated.html.twig',
                        [
                            'fullName' => $customer->getFullName(),
                            'customerProducts' => implode(', ', array_column($customer->getCustomerProductNames(), 'productName')),
                        ]
                    );
            }

            return new JsonResponse([
                '__notifications' => [
                    [
                        'type' => 'success',
                        'title' => $this->getTranslator()->trans('notification.activateCustomer.success.title', [], 'CustomerBundle'),
                        'message' => $this->getTranslator()->trans(
                            'notification.activateCustomer.success.message',
                            ['%name%' => $customer->getFullName() . ' (' . $user->getUsername() . ')'],
                            'CustomerBundle'
                        ),
                    ],
                ],
            ], Response::HTTP_OK);
        } else {
            throw new \Exception(
            $this->getTranslator()->trans(
                'notification.activateCustomer.error.message',
                [],
                'CustomerBundle'
            ), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function activate($user, $customer, $passwords)
    {
        $user->setIsActive(true);
        $user->setActivationTimestamp(new \DateTime());
        $user->setPassword($this->getUserManager()->encodePassword($user, $passwords['password']));
        $user->setActivationCode('');
        $this->getUserRepository()->save($user);
        $customer->setTransactionPassword($this->encodeTransactionPassword($user, $passwords['transactionPassword']));
        $this->getCustomerRepository()->save($customer);
    }

    public function sendResetPasswordAction(Request $request, $id)
    {
        $user = $this->getUserRepository()->find($id);

        if (!$user) {
            throw new \Doctrine\ORM\NoResultException();
        }

        $customer = $user->getCustomer();
        $temporaryPassword = $this->getContainer()->getParameter('customer_temp_password');
        $origin = $request->get('origin', $this->getParameter('asianconnect_url'));

        if ($user->isActivated()) {
            $user->setResetPasswordCode(
                $this->getUserManager()->encodeResetPasswordCode($user)
            );
            $user->setResetPasswordSentTimestamp(new \DateTime());
            $user->setPassword(
                $this->getUserManager()->encodePassword($user, $temporaryPassword)
            );
            $this->getUserRepository()->save($user);

            $resetPasswordCode = [
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'password' => $temporaryPassword,
                'resetPasswordCode' => $user->getResetPasswordCode(),
            ];

            $this->getUserManager()->sendResetPasswordEmail([
                'email' => $user->getEmail(),
                'originFrom' => $origin,
                'resetPasswordCode' => JWT::encode($resetPasswordCode, 'AMSV2'),
            ]);

            return new JsonResponse([
                '__notifications' => [
                    [
                        'type' => 'success',
                        'title' => $this->getTranslator()->trans('notification.resetPassword.success.title', [], 'CustomerBundle'),
                        'message' => $this->getTranslator()->trans(
                            'notification.resetPassword.success.message',
                            ['%name%' => $customer->getFullName() . ' (' . $user->getUsername() . ')'],
                            'CustomerBundle'
                        ),
                    ],
                ],
            ], Response::HTTP_OK);
        } else {
            throw new \Exception(
                $this->getTranslator()->trans(
                    'notification.resetPassword.error.message',
                    [],
                    'CustomerBundle'
                ), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function suspendAction(Request $request)
    {
        $id = $request->request->get('customerId');
        $repo = $this->getCustomerRepository();
        $customer = $repo->find($id);
        if (!$customer) {
            throw new \Doctrine\ORM\NoResultException;
        } elseif ($customer->isActive()) {
            $customer->suspend();
            $repo->save($customer);
            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', [
                    'type'      => 'success',
                    'title'     => $this->getTranslator()->trans('notification.suspended.title', [], 'CustomerBundle'),
                    'message'   => 'customer suspended',
                ]);

                return $this->redirect($request->headers->get('referer'), Response::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => [
                        'type'      => 'success',
                        'title'     => 'Activation',
                        'message'   => 'customer '. $customer->getFname() .' has been suspended',
                    ], Response::HTTP_OK
                ]);
            }
        } else {
            throw new \Exception('Customer is already suspended', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function activateAction(Request $request)
    {
        $id = $request->request->get('customerId');
        $repo = $this->getCustomerRepository();
        $customer = $repo->find($id);
        if (!$customer) {
            throw new \Doctrine\ORM\NoResultException;
        } elseif (!$customer->isActive()) {
            $customer->activate();
            $repo->save($customer);

            $notificationData = [
                'type'      => 'success',
                'title'     => 'Activation',
                'message'   => 'customer '. $customer->getFname() .' has been activated',
            ];

            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $notificationData);

                return $this->redirect($request->headers->get('referer'), Response::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $notificationData, Response::HTTP_OK
                ]);
            }
        } else {
            throw new \Exception('Customer is already activated', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function loginHistoryAction(Request $request)
    {
        $this->getSession()->save();
        $filters = [];
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_VIEW']);
        $filters = $request->request->all();
        $filters = array_merge($filters, $request->query->all());
        $results = $this->getManager()->getCustomerLoginHistory($filters);

        return new JsonResponse($results, JsonResponse::HTTP_OK);
    }

    /**
     * Get customer repository.
     *
     * @return \DbBundle\Repository\CustomerRepository
     */
    protected function getCustomerRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Customer');
    }

    //public function

    /**
     * Get user repository.
     *
     * @return \DbBundle\Repository\UserRepository
     */
    protected function getUserRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:User');
    }

    //public function

    /**
     * Get setting repository.
     *
     * @return \DbBundle\Repository\SettingRepository
     */
    protected function getSettingRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Setting');
    }

    /**
     * @return \ZendeskBundle\Manager\UserManager
     */
    protected function getZendeskUserManager()
    {
        return $this->getContainer()->get('zendesk.user_manager');
    }

    /**
     * Get media manager.
     *
     * @return \MediaBundle\Manager\MediaManager
     */
    protected function getMediaManager()
    {
        return $this->getContainer()->get('media.manager');
    }

    /**
     * Get Customer Manager.
     *
     * @return \CustomerBundle\Manager\CustomerManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('customer.manager');
    }

    protected function getUserManager()
    {
        return $this->getContainer()->get('user.manager');
    }

    public function encodeTransactionPassword(User $user, $transactionPassword): string
    {
        return $this->getPasswordEncoder()->encodePassword($user, $transactionPassword, User::getTransactionPasswordSalt());
    }

    protected function getPasswordEncoder()
    {
        return $this->get('security.password_encoder');
    }


    private function getMailer(): \AppBundle\Manager\MailerManager
    {
        return $this->getContainer()->get('app.mailer_manager');
    }

}
