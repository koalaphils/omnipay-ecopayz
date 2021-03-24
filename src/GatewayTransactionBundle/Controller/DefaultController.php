<?php

namespace GatewayTransactionBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Exceptions\FormValidationException;
use Doctrine\ORM\OptimisticLockException;

class DefaultController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_GATEWAY_TRANSACTION_VIEW']);

        return $this->render('GatewayTransactionBundle:Default:index.html.twig');
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_GATEWAY_TRANSACTION_VIEW']);
        $filters = $request->request->all();
        $results = $this->getManager()->getList($filters);
        $context = $this->createSerializationContext(['Default', '_link']);

        return $this->jsonResponse($results, Response::HTTP_OK, [], $context);
    }

    public function createAction(Request $request, $type)
    {
        $this->denyAccessUnlessGranted(['ROLE_GATEWAY_TRANSACTION_CREATE']);

        $form = $this->getManager()->prepareForm($type);
        $form->handleRequest($request);

        return $this->render('GatewayTransactionBundle:Default:create.html.twig', [
            'form' => $form->createView(),
            'type' => $type,
        ]);
    }

    public function updateAction(Request $request, $id, $type)
    {
        $this->denyAccessUnlessGranted(['ROLE_GATEWAY_TRANSACTION_UPDATE']);

        $form = $this->getManager()->prepareForm($type, $id);
        $form->handleRequest($request);

        return $this->render('GatewayTransactionBundle:Default:update.html.twig', [
            'form' => $form->createView(),
            'type' => $type,
        ]);
    }

    public function saveAction(Request $request, $id, $type)
    {
        $response = [];
        $statusCode = Response::HTTP_OK;

        if ($id === 'new') {
            $this->denyAccessUnlessGranted(['ROLE_GATEWAY_TRANSACTION_CREATE']);
        } else {
            $this->denyAccessUnlessGranted(['ROLE_GATEWAY_TRANSACTION_UPDATE']);
        }

        $form = $this->getManager()->prepareForm($type, $id);
        try {
            $gatewayTransaction = $this->getManager()->handleForm($form, $request);
            $response['data'] = $gatewayTransaction;
        } catch (FormValidationException $e) {
            $response['errors'] = $e->getErrors();
            $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
        } catch (OptimisticLockException $e) {
            $notifications = [
                'type' => 'error',
                'title' => $this->getTranslator()->trans('notification.error.lock.title', [], 'GatewayTransactionBundle'),
                'message' => $this->getTranslator()->trans('notification.error.lock.message', [], 'GatewayTransactionBundle'),
            ];

            return $this->jsonResponse(['__notifications' => [$notifications]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->response($request, $response, ['groups' => ['Default', '_link']], $statusCode);
    }

    protected function getGatewayTransactionRepository(): \DbBundle\Repository\GatewayTransactionRepository
    {
        return $this->getRepository('DbBundle:GatewayTransaction');
    }

    protected function getManager(): \GatewayTransactionBundle\Manager\GatewayTransactionManager
    {
        return $this->getContainer()->get('gateway_transaction.manager');
    }
}
