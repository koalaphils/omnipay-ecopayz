<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ApiBundle\Controller;

use AppBundle\Exceptions\FormValidationException;
use AppBundle\Helper\StrHelper;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Description of AbstractController
 *
 * @author cnonog
 */
abstract class AbstractController extends FOSRestController
{
    protected function view($data = null, $statusCode = null, array $headers = []): View
    {
        if($data instanceof ConstraintViolationList) {
            $violations = $this->serializeViolation($data);
            if ($statusCode === null) {
                $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
            }
            $view = View::create(['success' => false, 'errors' => $violations], $statusCode, $headers);
        } else {
            $view = View::create($data, $statusCode, $headers);
        }

        $format = $this->getRequestStack()->getCurrentRequest()->get('_format');
        if ($format !== null) {
            $view->setFormat($format);
        }
        $view->getContext()->setGroups(['Default', 'API']);
        $view->getContext()->setSerializeNull(true);

        return $view;
    }

    protected function serializeViolation(ConstraintViolationList $constraintViolationList): array
    {
        $violations = [];
        /* @var $constraintViolation ConstraintViolationInterface */
        foreach ($constraintViolationList as $constraintViolation) {
            $violations[] = [
                'field' => StrHelper::snakeCase($constraintViolation->getPropertyPath()),
                'message' => $constraintViolation->getMessage(),
            ];
        }

        return $violations;
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

    /**
     * @throws FormValidationException if the form is invalid.
     */
    protected function validateForm(Form $form): void
    {
        if (!$form->isValid()) {
            throw new FormValidationException($form);
        }
    }

    protected function isFormValid(FormInterface $form): bool
    {
        $isSubmitted = $form->isSubmitted();

        if ($isSubmitted) {
            $this->validateForm($form);
        }

        return $isSubmitted;
    }

    protected function handleRequest(FormInterface $form, Request $request): void
    {
        if ($request->getMethod() === 'GET') {
            $data = $request->query->all();
        } else {
            if ($request->getContent()) {
                $data =  json_decode($request->getContent(), true); 
                if (is_null($data)) {
                    $data = $request->request->all();
                }
            } else {
                $data = $request->request->all();
            }
        }
        
        $form->submit($data);
    }
}
