<?php

namespace PaymentBundle\Manager;

use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormBuilder;

class GatewayFormManager
{
    private $formConfigurations;
    private $formFactory;

    public function __construct(FormFactory $formFactory)
    {
        $this->formConfigurations = [];
        $this->formFactory = $formFactory;
        $this->generatedForms = [];
    }

    public function addFormConfiguration($mode, $configuration): self
    {
        $this->formConfigurations[$mode] = $configuration;

        return $this;
    }

    public function getForm($mode, $details = null, $formOptions = [], $formBuilder = null)
    {
        $form = $this->generateForm($mode, $details, $formOptions, $formBuilder);

        return $form;
    }

    public function getModes(): array
    {
        return $this->formConfigurations;
    }


    public function getMode($mode): array
    {
        if (!array_key_exists($mode, $this->formConfigurations)) {
            throw new \Exception(sprintf('Mode "%s" not found', $mode));
        }

        return $this->formConfigurations[$mode];
    }

    private function generateForm($mode, $details = null, $formOptions = [], $formBuilder = null)
    {
        if ($formBuilder === null) {
            $blockPrefix = $formOptions['block_prefix'] ?? 'form';
            unset($formOptions['block_prefix']);
            $formBuilder = $this->formFactory->createNamed($blockPrefix, \Symfony\Component\Form\Extension\Core\Type\FormType::class, $details, $formOptions);
        }

        $fields = $this->getMode($mode);

        foreach ($fields as $fieldName => $config) {
            $options = $config['options'] ?? [];

            $options['attr'] = $options['attr'] ?? [];
            $options['attr']['data-mode'] = $mode;
            $formBuilder->add($fieldName, $config['type'], $options);
        }

        return $formBuilder;
    }
}
