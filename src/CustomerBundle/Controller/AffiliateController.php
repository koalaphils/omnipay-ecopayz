<?php

namespace CustomerBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use CustomerBundle\Form\CustomerType;
use DbBundle\Entity\Customer;

class AffiliateController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_VIEW']);

        return $this->render('CustomerBundle:Affiliate:index.html.twig');
    }

    public function commissionPageAction(Request $request, int $id, string $activeTab): Response
    {
        $this->denyAccessUnlessGranted(['ROLE_AFFILIATE_COMMISSION_VIEW']);

        $customer = $this->getManager()->findById($id);
        $widget = $this->getWidgetManager()->getWidget('affiliateCommissionList', ['affiliate' => $customer->getId(), 'canAdd' => 1]);

        if ($request->isXmlHttpRequest() && $request->headers->has('X-WIDGET-REQUEST')) {
            $requestData = $request->request->all();
            $requestData = array_merge($requestData, $request->query->all());

            $widget = $widget->findChildren($request->headers->get('X-WIDGET-PATH'));

            $properties = array_merge($widget->getProperties(), $request->get('widget_property', []), \GuzzleHttp\json_decode($request->headers->get('X-SET-PROPERTIES', '[]'), true));

            $widget->setProperties($properties);
            $widget->init();
            $widget->run();

            $response = $this->getWidgetManager()->onActionWidget($widget, $request->headers->get('X-WIDGET-REQUEST'), $requestData);

            if ($response instanceof Response) {
                return $response;
            } elseif (is_array($response)) {
                return $this->jsonResponse($response);
            }

            return $response;
        }

        return $this->render(
            'CustomerBundle:Affiliate:TabPages/commissions.html.twig',
            ['customer' => $customer, 'widget' => $widget]
        );
    }

    public function convertAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_CONVERT_TO_CUSTOMER']);
        if (!$request->isXmlHttpRequest() && $request->get('callback', false) === false) {
            throw new \RuntimeException('Callback is undefined', JsonResponse::HTTP_EXPECTATION_FAILED);
        }

        /* @var $customer \DbBundle\Entity\Customer */
        $customer = $this->getManager()->getRepository()->find($id);
        if ($customer) {
            if ($customer->getIsAffiliate()) {
                $notification = [
                    'type' => 'error',
                    'title' => $this->getTranslator()->trans('notification.convertAffiliate.error.title', [], 'CustomerBundle'),
                    'message' => $this->getTranslator()->trans('notification.convertAffiliate.error.message', [], 'CustomerBundle'),
                ];
            } else {
                $customer->setIsAffiliate(true);
                $this->getManager()->save($customer);
                $notification = [
                    'type' => 'success',
                    'title' => $this->getTranslator()->trans('notification.convertAffiliate.success.title', [], 'CustomerBundle'),
                    'message' => $this->getTranslator()->trans('notification.convertAffiliate.success.message', [], 'CustomerBundle'),
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

    public function createPageAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_CREATE']);
        $status = true;
        $form = $this->createForm(CustomerType::class, null, [
            'action' => $this->getRouter()->generate('customer.create'),
            'guestType' => Customer::AFFILIATE,
        ]);
        $form->handleRequest($request);

        return $this->render('CustomerBundle:Affiliate:create.html.twig', ['form' => $form->createView()]);
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
        $validationGroups = ['default'];
        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_CREATE']);
            $customer = new Customer();
        } else {
            $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_UPDATE']);
            /* @var $customer \DbBundle\Entity\Customer */
            $customer = $this->getManager()->getRepository()->find($id);
        }

        if ('new' === $id) {
            $validationGroups[] = 'withPassword';
        }

        $form = $this->createForm(CustomerType::class, $customer, [
            'validation_groups' => $validationGroups,
            'guestType' => Customer::AFFILIATE,
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
            $redirectTo = $this->getRouter()->generate('affiliate.update_page', ['id' => $customer->getId()]) . '/profile';

            return $this->redirect($redirectTo, 301);
        }

        $this->getSession()->getFlashBag()->add('notifications', [
            'title' => $this->getTranslator()->trans('notification.error.title', [], 'CustomerBundle'),
            'message' => $this->getTranslator()->trans('notification.error.message', [], 'CustomerBundle'),
            'type' => 'error'
        ]);

        return $this->redirect($request->headers->get('referer'), 307);
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

    private function generateWidgetCollection(array $options = [], array $definitions = []): \AppBundle\Widget\CollectionWidget
    {
        return $this->getWidgetManager()->getWidget('collection', $options, $definitions);
    }
}
