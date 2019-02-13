<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ApiBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

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
    
    protected function getCurrenctRequest(): Request
    {
        return $this->getRequestStack()->getCurrentRequest();
    }


    protected function getRequestStack(): RequestStack
    {
        return $this->container->get('request_stack');
    }
    
    protected function createFormTypeBuilder(string $type, $data = null, array $options = array()): FormBuilderInterface
    {
        return $this->getFormFactory()->createBuilder($type, $data, $options);
    }
    
    protected function createNamedFormTypeBuilder($name, string $type, $data = null, array $options = array()): FormBuilderInterface
    {
        return $this->getFormFactory()->createNamedBuilder($name, $type, $data, $options);
    }


    protected function getFormRegistry(): FormRegistry
    {
        return $this->container->get('form.registry');
    }
    
    protected function getFormFactory(): \Symfony\Component\Form\FormFactory
    {
        return $this->container->get('form.factory');
    }

    protected function getTranslator(): TranslatorInterface
    {
        return $this->container->get('translator');
    }
}
