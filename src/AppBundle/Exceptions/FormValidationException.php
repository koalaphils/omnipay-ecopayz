<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Exceptions;

use Symfony\Component\Form\Form;

/**
 * Description of ValidationException
 *
 * @author cnonog
 */
class FormValidationException extends \Exception implements \JsonSerializable
{
    private $form;

    public function __construct(Form $form, $code = 421, \Throwable $previous = null)
    {
        $this->form = $form;
        parent::__construct('Form Invalid: ' . json_encode($this->getErrorMessages($this->form)), $code, $previous);
    }

    public function jsonSerialize()
    {
        return $this->getErrorMessagesWithFormId(true);
    }

    public function getErrors()
    {
        return $this->getErrorMessagesWithFormId($this->form, true);
    }

    private function getErrorMessages($form)
    {
        $errors = [];
        foreach ($form->getErrors() as $key => $error) {
            $errors[$key] = $error->getMessage();
        }
        foreach ($form as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
    }

    private function getErrorMessagesWithFormId($form, $flatten = false)
    {
        $errors = [];
        foreach ($form->getErrors() as $key => $error) {
            $view = $error->getOrigin()->createView();
            if (!$flatten) {
                $errors[$key] = [
                    'message' => $error->getMessage(),
                    'formId' => $view->vars['id'],
                    'fullName' => $view->vars['full_name'],
                ];
            } else {
                $errors[] = [
                    'message' => $error->getMessage(),
                    'formId' => $view->vars['id'],
                    'fullName' => $view->vars['full_name'],
                ];
            }
        }
        foreach ($form as $child) {
            if (!$child->isValid()) {
                if (!$flatten) {
                    $errors[$child->getName()] = $this->getErrorMessagesWithFormId($child, $flatten);
                } else {
                    $errors = array_merge($errors, $this->getErrorMessagesWithFormId($child, $flatten));
                }
            }
        }

        return $errors;
    }
}
