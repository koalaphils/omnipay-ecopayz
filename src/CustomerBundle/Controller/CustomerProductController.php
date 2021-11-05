<?php
namespace CustomerBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use ProductIntegrationBundle\Exception\IntegrationException;

use CustomerBundle\Events;
use CustomerBundle\Event\CustomerProductSaveEvent;
use CustomerBundle\Event\CustomerProductSuspendedEvent;
use CustomerBundle\Event\CustomerProductActivatedEvent;
use CustomerBundle\Form\CustomerProductType;
use DbBundle\Entity\CustomerProduct;

class CustomerProductController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_VIEW']);

        return $this->render('CustomerBundle:Product:index.html.twig', ['form' => $form]);
    }

    public function searchAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_VIEW']);

        $status = true;
        $filters = $request->request->all();
        if ($id !== 'all') {
            $filters['customerID'] = $id;
        }
        $results = $this->getManager()->getCustomerProductList($filters, true);

        return new JsonResponse($results, $status ? JsonResponse::HTTP_OK : JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function createAction(Request $request, $id)
    {
        $status = true;
        $filters = $results = [];
        try {
            $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_CREATE']);
            $form = $this->createForm(CustomerProductType::class, null, [
                'action' => $this->getRouter()->generate('customerProduct.save'),
                'customerID' => $id,
            ]);
            $form->handleRequest($request);
        } catch (\Exception $e) {
            $status = false;
            $errorMessage = 'Line error: ' . $e->getCode() . ' Message: ' . $e->getMessage();

            throw new \Exception($errorMessage);
        }

        return $this->render('CustomerBundle:Modal:create.html.twig', [
                'form' => $form->createView(),
        ]);
    }

    public function saveAction(Request $request, $id = 'new')
    {
        $status = true;
        $statusCode = JsonResponse::HTTP_OK;
        $message = $notification = null;
        $results = [];

        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse($results, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_PRODUCT_CREATE']);
            $customerProduct = new CustomerProduct();
        } else {
            $this->denyAccessUnlessGranted(['ROLE_CUSTOMER_PRODUCT_UPDATE']);
            $customerProduct = $this->_getCustomerProductRepository()->find($id);
        }

        $form = $this->createForm(CustomerProductType::class, $customerProduct);
        $data = $request->request->get('CustomerProduct');

        $form->handleRequest($request);
        if ($form->isValid()) {
            $customerProduct = $form->getData();
            $productEntity = $this->_getProductRepository()->find($data['product']);
            $customerProduct->setProduct($productEntity);            
            $manager = $this->getManager();

            $productName = $productEntity->getName();

            $this->dispatchEvent(Events::EVENT_CUSTOMER_PRODUCT_SAVE, new CustomerProductSaveEvent($customerProduct));

            $customerProduct->clearBalance();

            $this->_getCustomerProductRepository()->save($customerProduct);
            
            $message = [
                'type' => 'success',
                'title' => $this->getTranslator()->trans(
                    'notification.title',
                    ['%name%' => $productName],
                    'CustomerProductBundle'
                ),
                'text' => $this->getTranslator()->trans(
                    'notification.' . ($id === 'new' ? 'created' : 'updated'),
                    ['%name%' => $productName],
                    'CustomerProductBundle'
                ),
            ];
            $id = $customerProduct->getId();
        } else {
            $errors = $this->getManager()->getErrorMessages($form);
            $errors = ['errors' => array_dot($errors, '_', $form->getName() . '_', true)];

            return new JsonResponse($errors, 422);
        }

        return new JsonResponse(['status' => $status, 'message' => $message, 'result' => $id], $statusCode);
    }

    public function activateAction(Request $request, $id)
    {
        try {
            $customerProduct = $this->_getCustomerProductRepository()->find($id);
            if ($customerProduct === null) return $this->json([], 404);
            if ($customerProduct->getIsActive()) return $this->json(['error' => 'Customer Product is already activated!'], 400);

            $event = $this->getManager()->activate($customerProduct);
            $this->dispatchEvent(Events::EVENT_CUSTOMER_PRODUCT_ACTIVATED, $event);

            $message = [
                'type'      => 'success',
                'title'     => $this->getTranslator()->trans('notification.activated.title', [], 'CustomerProductBundle'),
                'message'   => $this->getTranslator()->trans('notification.activated.message', ['%username%' => $customerProduct->getUserName() ], 'CustomerProductBundle'),
            ];

            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $message);
                return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $message, JsonResponse::HTTP_OK,
                ]);
            }
        } catch (IntegrationException $ex) {
            return $this->json([
                '__notifications' =>  [
                    'type' => 'error',
                    'title' => 'Integration error, product cannot be activated. Try saving PIWIX account first.'
                ]
            ],200);
        }
    }

    public function suspendAction(Request $request, $id)
    {
        try {
            $customerProduct = $this->_getCustomerProductRepository()->find($id);

            if ($customerProduct === null) return $this->json([], 404);
            if (!$customerProduct->getIsActive()) return $this->json(['error' => 'Customer Product is already suspended!'], 400);

            $this->getManager()->suspend($customerProduct);
            $this->dispatchEvent(Events::EVENT_CUSTOMER_PRODUCT_SUSPENDED, new CustomerProductSuspendedEvent($customerProduct));

            $message = [
                'type'      => 'success',
                'title'     => $this->getTranslator()->trans('notification.suspended.title', [], 'CustomerProductBundle'),
                'message'   => $this->getTranslator()->trans('notification.suspended.message', ['%username%' => $customerProduct->getUserName() ], 'CustomerProductBundle'),
            ];

            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $message);
                return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $message, JsonResponse::HTTP_OK,
                ]);
            }
        } catch (IntegrationException $ex) {
            return $this->json([
                '__notifications' =>  [
                    'type' => 'error',
                    'title' => 'Integration error, product cannot be suspended. Try saving PIWIX account first.'
                ]
            ],200);
        }
    }

    protected function getManager()
    {
        return $this->getContainer()->get('customerproduct.manager');
    }

    protected function _getCustomerProductRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:CustomerProduct');
    }

    protected function _getProductRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Product');
    }
}
