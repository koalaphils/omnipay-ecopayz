<?php

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiBundle\Serializer\Normalizer;

use JMS\Serializer\Context;
use JMS\Serializer\Handler\FormErrorHandler as JMSFormErrorHandler;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\XmlSerializationVisitor;
use Symfony\Component\Form\Form;
use JMS\Serializer\YamlSerializationVisitor;
use JMS\Serializer\GenericSerializationVisitor;
use Symfony\Component\Form\FormError;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Extend the JMS FormErrorHandler to include more informations when using the ViewHandler.
 */
class FormErrorHandler extends JMSFormErrorHandler
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        parent::__construct($translator);
    }

    public function serializeFormToXml(XmlSerializationVisitor $visitor, Form $form, array $type, Context $context = null)
    {
        if ($context) {
            $statusCode = $context->attributes->get('status_code');
            $message = $context->attributes->get('message');

            if ($statusCode->isDefined()) {
                if (null === $visitor->document) {
                    $visitor->document = $visitor->createDocument(null, null, true);
                }

                $codeNode = $visitor->document->createElement('code');
                $visitor->getCurrentNode()->appendChild($codeNode);
                $codeNode->appendChild($context->getNavigator()->accept($statusCode->get(), null, $context));

                $messageNode = $visitor->document->createElement('message');
                $visitor->getCurrentNode()->appendChild($messageNode);
                $messageNode->appendChild($context->getNavigator()->accept(
                    $message->isDefined() ? $message->get() : 'Validation Failed',
                    null,
                    $context
                ));

                $errorsNode = $visitor->document->createElement('errors');
                $visitor->getCurrentNode()->appendChild($errorsNode);
                $visitor->setCurrentNode($errorsNode);
                parent::serializeFormToXml($visitor, $form, $type);
                $visitor->revertCurrentNode();

                return;
            }
        }

        return parent::serializeFormToXml($visitor, $form, $type);
    }

    public function serializeFormToJson(JsonSerializationVisitor $visitor, Form $form, array $type, Context $context = null)
    {
        return $this->flattenFormErrorsToArray($visitor, $form);
    }
    
    

    public function serializeFormToYml(YamlSerializationVisitor $visitor, Form $form, array $type, Context $context = null)
    {
        $isRoot = null === $visitor->getRoot();
        $result = $this->adaptFormArray(parent::serializeFormToYml($visitor, $form, $type), $context);

        if ($isRoot) {
            $visitor->setRoot($result);
        }

        return $result;
    }

    private function flattenFormErrorsToArray(GenericSerializationVisitor $visitor, Form $data)
    {
        $isRoot = null === $visitor->getRoot();

        $form = new \ArrayObject();
        $errors = [];

        foreach ($data->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
        
        if ($errors) {
            $form['errors'] = $errors;
        }

        $children = [];

        foreach ($data->all() as $child) {
            if ($child instanceof Form) {
                $children[$child->getName()] = $this->flattenFormErrorsToArray($visitor, $child);
            }
        }

        if ($children) {
            $form['children'] = $children; // remove key children
        }

        if ($isRoot) {
            $visitor->setRoot($form);
        }
        
        return $form;
    }

    private function adaptFormArray(\ArrayObject $serializedForm, Context $context = null)
    {
        $statusCode = $this->getStatusCode($context);
        $message = $this->getMessage($context);

        if (null !== $statusCode) {
            return [
                'code' => $statusCode,
                'message' => $message,
                'errors' => $serializedForm,
            ];
        }

        return $serializedForm;
    }

    private function getStatusCode(Context $context = null)
    {
        if (null === $context) {
            return;
        }

        $statusCode = $context->attributes->get('status_code');
        if ($statusCode->isDefined()) {
            return $statusCode->get();
        }
    }

    private function getMessage(Context $context = null)
    {
        if ($context === null) {
            return;
        }

        $message = $context->attributes->get('message');
        if ($message->isDefined()) {
            return $message->get();
        }
    }
}
