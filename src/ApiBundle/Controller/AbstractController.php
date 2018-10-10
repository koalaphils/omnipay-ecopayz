<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;

/**
 * Description of AbstractController
 *
 * @author cnonog
 */
abstract class AbstractController extends FOSRestController
{
    protected function view($data = null, $statusCode = null, array $headers = []): View
    {
        $view = View::create($data, $statusCode, $headers);
        $format = $this->getRequestStack()->getCurrentRequest()->get('_format');
        if ($format !== null) {
            $view->setFormat($format);
        }
        $view->getContext()->setGroups(['Default', 'API']);
        $view->getContext()->setSerializeNull(true);

        return $view;
    }

    protected function getRepository($className)
    {
        return $this->getDoctrine()->getRepository($className);
    }
    
    protected function getCurrenctRequest(): \Symfony\Component\HttpFoundation\Request
    {
        return $this->getRequestStack()->getCurrentRequest();
    }


    protected function getRequestStack(): \Symfony\Component\HttpFoundation\RequestStack
    {
        return $this->container->get('request_stack');
    }
}
