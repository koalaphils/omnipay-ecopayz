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

class DefaultController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_VIEW']);
        $this->getSettingRepository()->updateSetting('customer', 'counter', 0);

        return $this->render('CustomerBundle:Default:index.html.twig');
    }

    public function searchAction(Request $request)
    {
        $this->getSession()->save();
        $status = true;
        $filters = $results = [];
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_VIEW']);
        $filters = $request->request->all();
        $filters = array_merge($filters, $request->query->all());
        $results = $this->getManager()->getCustomerList($filters);

        return new JsonResponse($results, $status ? JsonResponse::HTTP_OK : JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function createAction(Request $request)
    {
        $status = true;
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_CREATE']);
        $form = $this->createForm(CustomerType::class, null, [
            'action' => $this->getRouter()->generate('customer.save'),
        ]);
        $form->handleRequest($request);

        return $this->render('CustomerBundle:Default:create.html.twig', ['form' => $form->createView()]);
    }

    public function saveAction(Request $request, $id = 'new')
    {
        $status = true;
        $validationGroups = ['default'];

        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_CREATE']);
            $customer = new Customer();
        } else {
            $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_UPDATE']);
            $customer = $this->getCustomerRepository()->find($id);
        }

        if ('new' === $id) {
            $validationGroups[] = 'withPassword';
        }

        $form = $this->createForm(CustomerType::class, $customer, [
            'validation_groups' => $validationGroups,
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
            } else {
                //$customer->getUser()->setPassword($oldPassword);
            }

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

            if (is_null($customer->getUser()->getZendeskId())) {
                $logDir = $this->getContainer()->get('kernel')->getLogDir();
                $rootDir = $this->container->get('kernel')->getRootDir();
                $process = new Process('nohup ' . $this->container->getParameter('php_command') . " $rootDir/console customer:create-zendesk " . $customer->getId() . ' ' . $this->getUser()->getUsername() . " >> $logDir/zendesk.log 2>&1 &");
                $process->run();
            }

            return $this->redirectToRoute('customer.update_page', ['id' => $customer->getId()]);
        }

        return $this->redirect($request->headers->get('referer'), 307);
    }

    public function updateAction(Request $request, $id, $activeTab)
    {
        $status = true;
        $guestType = Customer::AFFILIATE;
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_UPDATE']);

        $customer = $this->getCustomerRepository()->findById($id);
     
        $this->getMenuManager()->setActive('customer.affiliate');
        $form = $this->createForm(CustomerType::class, $customer, [
            'action' => $this->getRouter()->generate('affiliate.save', ['id' => $id]),
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

        return $this->render('CustomerBundle:Affiliate:update.html.twig', [
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
        ]);
    }

    public function securitySaveAction(Request $request, $id)
    {
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
                $this->getManager()->save($data);
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
        $customer = $this->getCustomerRepository()->find($id);
        if (!$customer) {
            throw new \Doctrine\ORM\NoResultException();
        }
        if (!$customer->isVerified()) {
            $customer->setVerifiedAt(new \DateTime());
            $this->getCustomerRepository()->save($customer);

            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', [
                    'type' => 'success',
                    'title' => $this->getTranslator()->trans('notification.verified.title', [], 'CustomerBundle'),
                    'message' => $this->getTranslator()->trans(
                        'notification.verified.message',
                        ['%name%' => $customer->getFName() . ' ' . $customer->getLName() . '(' . $customer->getUser()->getUsername() . ')'],
                        'CustomerBundle'
                    ),
                ]);

                return $this->redirect($request->headers->get('referer'), 200);
            }

            return new JsonResponse([
                '__notifications' => [[
                    'type' => 'success',
                    'title' => $this->getTranslator()->trans('notification.verified.title', [], 'CustomerBundle'),
                    'message' => $this->getTranslator()->trans(
                        'notification.verified.message',
                        [
                            '%name%' => $customer->getFName() . ' ' . $customer->getLName() . '(' . $customer->getUser()->getUsername() . ')',
                        ],
                        'CustomerBundle'
                    ),
                ], ],
            ], 200);
        } else {
            throw new \Exception('Customer is already verified', 422);
        }
    }

    public function documentListAction($id)
    {
        $files = $this->getManager()->getDocuments($id);

        return new JsonResponse($files);
    }

    public function saveDocumentAction(Request $request, $id)
    {
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
        $this->getSession()->save();

        $customer = $this->getCustomerRepository()->find($id);
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
        $this->getSession()->save();

        if (!is_null($request->get('index', null))) {
            $customer = $this->getCustomerRepository()->find($id);
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
        $this->getSession()->save();
        $customer = $this->getCustomerRepository()->find($id);

        if ($customer) {
            $contactForm = $this->createForm(ContactsType::class, $customer, []);
            $contactForm->handleRequest($request);

            if ($contactForm->isSubmitted() && $contactForm->isValid()) {
                $data = $contactForm->getData();

                $this->getManager()->save($data);

                return new JsonResponse(['__notifications' => [[
                        'type' => 'success',
                        'title' => $this->getTranslator()->trans('notification.contact.title', [], 'CustomerBundle'),
                        'message' => $this->getTranslator()->trans('notification.contact.message', [], 'CustomerBundle'),
                    ], ],
                ], 200);
            }
        }
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
        $this->getSession()->save();
        $customer = $this->getCustomerRepository()->find($id);

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
        $this->getSession()->save();
        $customer = $this->getCustomerRepository()->find($id);
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
            $customer = $this->getCustomerRepository()->find($id);
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
}
