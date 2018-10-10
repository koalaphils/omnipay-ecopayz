<?php

namespace AuditBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends AbstractController
{
    public function indexAction($type)
    {
        $this->denyAccessUnlessGranted(['ROLE_AUDIT_VIEW']);
        $logDetails = $this->getManager()->getLogDetails();

        return $this->render(
            'AuditBundle:Default:index.html.twig', [
                'type' => $type,
                'logDetails' => $logDetails,
        ]);
    }

    public function searchAction(Request $request, $type)
    {
        $this->denyAccessUnlessGranted(['ROLE_AUDIT_VIEW']);
        $filters = array_merge($request->request->all(), ['type' => $type]);
        $results = $this->getManager()->getList($filters);

        $context = $this->createSerializationContext(['Default', '_link']);

        return $this->jsonResponse($results, Response::HTTP_OK, [], $context);
    }

    public function redirectAction($id)
    {
        $this->denyAccessUnlessGranted(['ROLE_AUDIT_VIEW']);

        $options = $this->getManager()->redirect($id);

        return $this->redirectToRoute($options['route'], $options['params']);
    }

    protected function getAuditRepository(): \DbBundle\Repository\AuditRevisionRepository
    {
        return $this->getRepository('DbBundle:AuditRevision');
    }

    protected function getManager(): \AuditBundle\Manager\AuditManager
    {
        return $this->getContainer()->get('audit.manager');
    }
}
