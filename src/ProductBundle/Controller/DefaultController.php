<?php

namespace ProductBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use ProductBundle\Form\ProductType;
use DbBundle\Entity\Product;
use DbBundle\Entity\AuditRevisionLog;
use DbBundle\Entity\Interfaces\AuditInterface;
use AppBundle\Exceptions\FormValidationException;

class DefaultController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_PRODUCT_VIEW']);
        $authorizedToDelete = $this->get('security.authorization_checker')->isGranted('ROLE_PRODUCT_DELETE');
        $validationGroups = ['default'];
        $productForm = $this->createForm(ProductType::class, null, [
            'action' => $this->getRouter()->generate('product.save'),
            'validation_groups' => $validationGroups,
        ]);
        
        return $this->render('ProductBundle:Default:index.html.twig', ['form' => $productForm->createView(), 'authorizedToDelete' => $authorizedToDelete]);
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_PRODUCT_VIEW']);
        $status = true;
        $filters = $results = [];
        $filters = $request->request->all();
        $results = $this->getManager()->getProductList($filters);

        return new JsonResponse($results, $status ? JsonResponse::HTTP_OK : JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function searchWithoutBetadminAction(Request $request)
    {
        $status = true;
        $filters = $results = [];
        $this->denyAccessUnlessGranted(['ROLE_PRODUCT_VIEW']);
        $filters = $request->request->all();
        $filters['betadminToSync'] = true;
        $filters['id'] = 9;
        $results = $this->getManager()->getProductList($filters);

        return new JsonResponse($results, $status ? JsonResponse::HTTP_OK : JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_PRODUCT_CREATE']);
        $status = true;
        $form = $this->createForm(ProductType::class, null, [
            'action' => $this->getRouter()->generate('product.save'),
        ]);
        $form->handleRequest($request);

        $render = $this->render('ProductBundle:Default:create.html.twig', [
            'form' => $form->createView(),
        ]);

        return $render;
    }

    public function saveAction(Request $request, $id = 'new')
    {
        $message = null;
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse([], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
        $validationGroups = ['default'];
        $commission = null;
        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_PRODUCT_CREATE']);
            $productForm = new \ProductBundle\Request\ProductFormRequest();
            $validationGroups[] = 'new';
        } else {
            $this->denyAccessUnlessGranted(['ROLE_PRODUCT_UPDATE']);
            $product = $this->getProductRepository()->find($id);
            $commission = $this->getProductCommissionRepository()->getProductCommissionOfProduct($id);
            $productForm = \ProductBundle\Request\ProductFormRequest::formEntity($product);
            if ($commission !== null) {
                $productForm->setProductCommission($commission);
            }
            $validationGroups[] = 'update';
        }

        $form = $this->createForm(ProductType::class, $productForm, [
            'validation_groups' => $validationGroups,
        ]);
        try {
            $product = $this->getManager()->handleForm($form, $request);
            $message = [
                'type' => 'success',
                'title' => $this->getTranslator()->trans(
                    'notification.title',
                    [],
                    'ProductBundle'
                ),
                'text' => $this->getTranslator()->trans(
                    'notification.' . ($id === 'new' ? 'created' : 'updated'),
                    ['%name%' => $product->getName()],
                    'ProductBundle'
                ),
            ];
        } catch (FormValidationException $e) {
            $errors = $this->getManager()->getErrorMessages($form);
            $errors = ['errors' => array_dot($errors, '_', $form->getName() . '_', true)];

            return new JsonResponse($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(['status' => true, 'message' => $message, 'result' => $id], JsonResponse::HTTP_OK);
    }

    public function updateAction(Request $request, $id)
    {
        $this->denyAccessUnlessGranted(['ROLE_PRODUCT_UPDATE']);
        $status = true;
        $this->getMenuManager()->setActive('product.list');

        $product = $this->getProductRepository()->find($id);
        $form = $this->createForm(ProductType::class, $product, [
            'action' => $this->getRouter()->generate('product.save', ['id' => $id]),
        ]);
        $form->handleRequest($request);

        return $this->render('ProductBundle:Default:update.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
        ]);
    }

    public function suspendAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_PRODUCT_SUSPEND']);
        
        $id = $request->request->get('productId');
        $product = $this->getProductRepository()->find($id);
        if (!$product) {
             throw new \Doctrine\ORM\NoResultException;
        } else if ($product->getIsActive()) {
            $product->suspend();
            $this->getProductRepository()->save($product);
            $message = [
                'type'      => 'success',
                'title'     => $this->getTranslator()->trans('notification.suspended.title', [], 'ProductBundle'),
                'message'   => $this->getTranslator()->trans('notification.suspended.message', ['%name%' => $product->getName() . "(" . $product->getCode() . ")", ], 'ProductBundle')
            ];
            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $message);
                return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $message, JsonResponse::HTTP_OK]);
            }
        } else {
            throw new \Exception('Product is already suspended', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function activateAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_PRODUCT_ACTIVATE']);

        $id = $request->request->get('productId');
        $product = $this->getProductRepository()->find($id);
        if (!$product) {
             throw new \Doctrine\ORM\NoResultException;
        } else if (!$product->getIsActive()) {
            $product->activate();
            $this->getProductRepository()->save($product);
            $message = [
                'type'      => 'success',
                'title'     => $this->getTranslator()->trans('notification.activated.title', [], 'ProductBundle'),
                'message'   => $this->getTranslator()->trans('notification.activated.message', ['%name%' => $product->getName() . "(" . $product->getCode() . ")", ], 'ProductBundle')
            ];
            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $message);
                return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $message, JsonResponse::HTTP_OK]);
            }
        } else {
            throw new \Exception('Product is already activated', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function deleteAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_PRODUCT_DELETE']);
        if(!$this->get('security.authorization_checker')->isGranted('ROLE_PRODUCT_DELETE')) {
            return new JsonResponse($result, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
        $id = $request->request->get('productId');
        $product = $this->getProductRepository()->find($id);
        if (!$product) {
             throw new \Doctrine\ORM\NoResultException;
        } else if (!$product->isDeleted()) {
            $product->setUniqueCode();
            $product->setUniqueName();
            $product->setDateTimeToDelete(new \DateTime());
            $this->getProductRepository()->save($product);
            $this->_createDeleteRevision($product);
            
            $message = [
                'type'      => 'success',
                'title'     => $this->getTranslator()->trans('notification.deleted.title', [], 'ProductBundle'),
                'message'   => $this->getTranslator()->trans('notification.deleted.message', ['%name%' => $product->getName() . "(" . $product->getCode() . ")", ], 'ProductBundle')
            ];
            if (!$request->isXmlHttpRequest()) {
                $this->getSession()->getFlashBag()->add('notifications', $message);
                return $this->redirect($request->headers->get('referer'), JsonResponse::HTTP_OK);
            } else {
                return new JsonResponse([
                    '__notifications' => $message, JsonResponse::HTTP_OK]);
            }
        } else {
            throw new \Exception('Product is already deleted', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Get product repository.
     *
     * @return \DbBundle\Repository\ProductRepository
     */
    protected function getProductRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Product');
    }

    protected function getProductCommissionRepository(): \DbBundle\Repository\ProductCommissionRepository
    {
        return $this->getDoctrine()->getRepository(\DbBundle\Entity\ProductCommission::class);
    }

    /**
     * Get Product Manager.
     *
     * @return \ProductBundle\Manager\ProductManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('product.manager');
    }

    /**
     * Get Audit Manager.
     *
     * @return \AuditBundle\Manager\AuditManager
     */
    protected function getAuditManager()
    {
        return $this->getContainer()->get('audit.manager');
    }

    private function _createDeleteRevision(Product $entity)
    {
        $auditRevision = $this->getAuditManager()->createRevision();
        $this->_audit(
            $entity,
            AuditRevisionLog::OPERATION_DELETE,
            AuditRevisionLog::CATEGORY_PRODUCT,
            $auditRevision
        );
        $this->getAuditManager()->save($auditRevision);
    }

    /**
     * @param AuditInterface $entity
     * @param string $operation
     * @param string $operationCategory
     * @param object $auditRevision
     */
    private function _audit(AuditInterface $entity, $operation, $operationCategory, $auditRevision)
    {
        $auditRevision->addLog(
            $this->getAuditManager()->createRevisionLog($entity , $operation, $operationCategory)
        );
    }
}
