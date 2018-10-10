<?php

namespace GatewayTransactionBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GatewayLogController extends AbstractController
{
    public function indexAction()
    {
        $this->denyAccessUnlessGranted(['ROLE_GATEWAY_LOG_VIEW']);

        return $this->render('GatewayTransactionBundle:Log:index.html.twig');
    }

    public function searchAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_GATEWAY_LOG_VIEW']);
        $filters = $request->request->all();
        $results = $this->getManager()->getList($filters);
        $context = $this->createSerializationContext(['Default', '_link']);

        return $this->jsonResponse($results, Response::HTTP_OK, [], $context);
    }

    public function redirectAction($id)
    {
        $this->denyAccessUnlessGranted(['ROLE_GATEWAY_LOG_VIEW']);

        $options = $this->getManager()->redirect($id);

        return $this->redirectToRoute($options['route'], $options['params']);
    }

    protected function getGatewayLogRepository(): \DbBundle\Repository\GatewayLogRepository
    {
        return $this->getRepository('DbBundle:GatewayTransaction');
    }

    protected function getManager(): \GatewayTransactionBundle\Manager\GatewayLogManager
    {
        return $this->getContainer()->get('gateway_log.manager');
    }
}
