<?php

namespace AppBundle\Widget\Page;

use AppBundle\Exceptions\FormValidationException;
use AppBundle\Form\Type\RepeatableRecordType;
use AppBundle\Form\Type\RepeatableType;
use AppBundle\Form\Type\SwitchType;
use AppBundle\Widget\AbstractPageWidget;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use JMS\Serializer\SerializationContext;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class FormWidget extends AbstractPageWidget
{
    private $formBuilder;
    /**
     *
     * @var FormInterface
     */
    private $form;
    private $formView;
    private $twig;
    private $groups;
    private $data;

    protected function onInit(): void
    {
        $this->groups = [];
    }


    public function onRun(): void
    {
        $this->setProperty('fields.btnSave', [
            'type' => 'submit',
            'options' => [
                'label' => 'Save',
                'attr' => [
                    'class' => 'btn btn-success ' . $this->getFullId() . '_button'
                ]
            ],
            'group' => 'Buttons',
        ]);
        if ($this->property('hasCancel', true)) {
            $cancelPath = $this->property('cancelPath', null);
            if (is_array($cancelPath)) {
                $cancelPath = $this->getRouter()->generate($this->property('cancelPath.route'), $this->property('cancelPath.params', []));
            }
            $btnCancelAttribute = ['class' => 'btn btn-inverse ' . $this->getFullId() . '_button'];
            if ($cancelPath !== null) {
                $btnCancelAttribute['data-redirect'] = $cancelPath;
            }
            $this->setProperty('fields.btnCancel', [
                'type' => 'button',
                'options' => [
                    'label' => 'Cancel',
                    'attr' => $btnCancelAttribute,
                ],
                'group' => 'Buttons',
            ]);
        }

        $formFactory = $this->getFormFactory();
        $class = $this->property('modelClass');
        $propertyData = $this->property('data', null);

        if (is_string($propertyData) && substr($propertyData, 0, 12) === '@pageManager') {
            $dataName = substr($propertyData, 13);
            $entity = $this->getPageManager()->getData($dataName);
            if (get_class($entity) !== $class) {
                $this->data = call_user_func([$class, 'fromEntity'], $entity);
            }
        } elseif (is_array($propertyData)) {
            $callback = $this->getCallableFromArray($propertyData);
            $expressionLanguage = new ExpressionLanguage();
            $arguments = [];
            foreach ($this->property('data.arguments', []) as $argument) {
                if (is_string($argument) && substr($argument, 0, 2) === '@=') {
                    $arguments[] = $expressionLanguage->evaluate(substr($argument, 2), ['app' => $this->getAppVariable(), 'pageManager' => $this->getPageManager()]);
                } else {
                    $arguments[] = $argument;
                }
            }

            $entity = call_user_func_array($callback, $arguments);
            if (is_array($entity)) {
                $this->data = call_user_func([$class, 'fromArray'], $entity);
            } elseif (get_class($entity) !== $class) {
                $this->data = call_user_func([$class, 'fromEntity'], $entity);
            }
        }

        if ($this->data === null) {
            $this->data = new $class();
        }

        $formOption = array_merge([
            'data_class' => $this->property('modelClass'),
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ], $this->property('options', []));

        $builder = $formFactory->createNamedBuilder($this->getFullId() . '_data', FormType::class, $this->data, $formOption);
        $this->buildForm($builder);

        $this->formBuilder = $builder;
    }

    public function getGroup(string $name): array
    {
        return $this->groups[$name] ?? [];
    }

    public function hasGroup(string $name): bool
    {
        return array_has($this->groups, $name);
    }

    public function getGroupsNotDefaultAndButton(): array
    {
        $groups = $this->getGroups();
        unset($groups['Default']);
        unset($groups['Buttons']);

        return $groups;
    }

    public function getGroupId(string $name): string
    {
        return str_replace(' ', '', ucwords($this->getFullId() . ' ' . $name));
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    protected function onRender(string $blockSuffix, array &$options): void
    {
        if ($this->form === null) {
            $this->form = $this->formBuilder->getForm();
            $this->formView = $this->form->createView();
            $this->getTemplateFormRenderer()->setTheme($this->formView, ['AppBundle:Widget:Page/form.html.twig']);
            foreach ($this->formView->children as $childName => $field) {
                $this->setPageWidgetToForm($field, $this->form->get($childName));
            }
        }

        $options['form'] = $this->formView;
    }

    protected function setPageWidgetToForm(FormView $formView, FormInterface $form)
    {
        $formView->vars['pageWidget'] = $this;
        $formView->vars['size'] = $form->getConfig()->getAttribute('size', 12);
        $formView->vars['properties'] = $form->getConfig()->getAttribute('fieldProperties');
        if ($formView->count() > 0) {
            foreach ($formView->children as $childName => $child) {
                $this->setPageWidgetToForm($child, $form->get($childName));
            }
        }
    }

    protected function getBlockName(): string
    {
        return 'formWidget';
    }

    public static function defineDetails(): array
    {
        return [
            'title' => 'Form'
        ];
    }

    public function onSave(array $data)
    {
        $request = new Request([], [$this->getFullId() . '_data' => $data]);
        $request->setMethod('POST');

        $form = $this->formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $statusCode = JsonResponse::HTTP_CREATED;
            $modelData = $form->getData();

            if ($this->hasProperty('handler')) {
                $handlerCallable = $this->getCallable($this->property('handler'));
                if (!empty($handlerCallable)) {
                    $modelData = call_user_func($handlerCallable, $modelData);
                }
            }

            $response['data'] = $modelData;
            if ($this->hasProperty('notifications.success')) {
                $response['__notifications'] = [
                    [
                        'title' => $this->property('notifications.success.title', 'Saved'),
                        'message' => $this->property('notifications.success.message', 'Saved'),
                        'type' => 'success',
                    ]
                ];
            }

            if ($this->hasProperty('redirectOnSuccess')) {
                $params = [];
                foreach ($this->property('redirectOnSuccess.params', []) as $paramName => $paramValue) {
                    if (!is_array($paramValue)) {
                        $params[$paramName] = $this->getPageManager()->getExpressionLanguage()->evaluate($paramValue, ['data' => $modelData]);
                    } elseif (isset($paramValue['type']) && $paramValue['type'] === 'static') {
                        $params[$paramName] = $paramValue['value'];
                    }
                }
                $response['redirect'] = $this->getRouter()->generate($this->property('redirectOnSuccess.route'), $params);
            }
        } else {
            $formValidation = new FormValidationException($form);
            $statusCode = JsonResponse::HTTP_UNPROCESSABLE_ENTITY;
            $response['errors'] = $formValidation->getErrors();
        }

        return $this->jsonResponse($response, $statusCode);
    }

    public function buildField(FormBuilderInterface $builder, string $name, array $info): void
    {
        call_user_func([$this, 'generate' . ucwords($info['type'])], $builder, $name, $info);
    }

    public function repeatablePreSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();
        $fields = $form->getConfig()->getAttribute('fields');
        if ($form->getConfig()->getAttribute('repeatable')) {
            foreach ($data as $index => $value) {
                $recordForm = $this->getFormFactory()->createNamedBuilder($index, RepeatableRecordType::class, null, ['auto_initialize' => false]);
                foreach ($fields as $fieldName => $fieldConfig) {
                    $options = $fieldConfig['options'] ?? [];
                    $options['auto_initialize'] = false;
                    $fieldConfig['options'] = $options;
                    $recordForm->add(call_user_func([$this, 'generate' . ucwords($fieldConfig['type'])], $fieldName, $fieldConfig));
                }
                $form->add($recordForm->getForm());
            }
        }
    }

    public function repeatablePreSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();
        $fields = $form->getConfig()->getAttribute('fields');

        // Delete
        foreach ($form as $name => $child) {
            if (!isset($data[$name])) {
                $form->remove($name);
            }
        }

        // Add all additional rows
        foreach ($data as $name => $value) {
            if (!$form->has($name)) {
                $recordForm = $this->getFormFactory()->createNamedBuilder($name, RepeatableRecordType::class, null, ['auto_initialize' => false]);
                foreach ($fields as $fieldName => $fieldConfig) {
                    $options = $fieldConfig['options'] ?? [];
                    $options['auto_initialize'] = false;
                    $fieldConfig['options'] = $options;
                    $recordForm->add(call_user_func([$this, 'generate' . ucwords($fieldConfig['type'])], $fieldName, $fieldConfig));
                }
                $form->add($recordForm->getForm());
            }
        }
    }

    public function repeatableOnSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();

        $toDelete = [];

        foreach ($data as $name => $child) {
            if (!$form->has($name)) {
                $toDelete[] = $name;
            }
        }

        foreach ($toDelete as $name) {
            unset($data[$name]);
        }

        $event->setData($data);
    }

    protected function getView(): string
    {
        return 'AppBundle:Widget:Page/form.html.twig';
    }

    public function getFormView(): FormView
    {
        if ($this->form === null) {
            $this->form = $this->formBuilder->getForm();
            $this->formView = $this->form->createView();
            $this->getTemplateFormRenderer()->setTheme($this->formView, ['AppBundle:Widget:Page/form.html.twig']);
            foreach ($this->formView->children as $childName => $field) {
                $this->setPageWidgetToForm($field, $this->form->get($childName));
            }
        }

        return $this->formView;
    }

    private function buildForm(FormBuilderInterface $builder): void
    {
        $this->addTransformer($builder);
        $fields =  $this->property('fields', []);
        $builder->setAttribute('fields', $fields);
        foreach ($fields as $name => $info) {
            $group = $info['group'] ?? 'Default';
            $this->groups[$group][] = $name;
            $builder->add(call_user_func([$this, 'generate' . ucwords($info['type'])], $name, $info));
        }
    }

    private function getFormFactory(): FormFactoryInterface
    {
        return $this->container->get('form.factory');
    }

    # Field Type Initializations

    private function generateText(string $name, array $info = []): FormBuilderInterface
    {
        $field = $this->getFormFactory()->createNamedBuilder($name, TextType::class, null, $info['options'] ?? []);
        $field->setAttribute('size', $info['size'] ?? 12);
        $field->setAttribute('fieldProperties', $info);

        return $field;
    }

    private function generateHidden(string $name, array $info = []): FormBuilderInterface
    {
        $field = $this->getFormFactory()->createNamedBuilder($name, HiddenType::class, null, $info['options'] ?? []);
        $field->setAttribute('fieldProperties', $info);

        return $field;
    }

    private function generateDatepicker(string $name, array $info = []): FormBuilderInterface
    {
        $field = $this->getFormFactory()->createNamedBuilder($name, DateType::class, null, $info['options'] ?? []);
        $field->setAttribute('size', $info['size'] ?? 12);
        $field->setAttribute('fieldProperties', $info);

        return $field;
    }

    private function generateRepeatable(string $name, array $info = []): FormBuilderInterface
    {
        $options = $info['options'] ?? [];
        $options['auto_initialize'] = false;

        $repeatableField = $this->getFormFactory()->createNamedBuilder($name, RepeatableType::class, null, $options);
        $repeatableField->setAttribute('fields', $info['fields']);
        $repeatableField->setAttribute('repeatable', true);
        $repeatableField->setAttribute('size', $info['size'] ?? 12);

        $prototype = $repeatableField->create('__name__', RepeatableRecordType::class);
        foreach ($info['fields'] as $fieldName => $fieldConfig) {
            $prototype->add(call_user_func([$this, 'generate' . ucwords($fieldConfig['type'])], $fieldName, $fieldConfig));
        }
        $repeatableField->setAttribute('prototype', $prototype->getForm());

        $repeatableField->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'repeatablePreSetData']);
        $repeatableField->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'repeatablePreSubmit']);
        $repeatableField->addEventListener(FormEvents::SUBMIT, [$this, 'repeatableOnSubmit'], 50);
        $repeatableField->setAttribute('fieldProperties', $info);

        return $repeatableField;
    }

    private function generateSelect2(string $name, array $info = []): FormBuilderInterface
    {
        $options = $info['options'] ?? [];
        $field = $this->getFormFactory()->createNamedBuilder($name, \AppBundle\Form\Type\Select2Type::class, null, $options);
        $field->setAttribute('size', $info['size'] ?? 12);
        $field->setAttribute('fieldProperties', $info);

        if (array_has($info, 'transformer.view')) {
            $tranformer = array_get($info, 'transformer.view');
            if (is_string($tranformer)) {
                $tranformer = $this->container->get(substr($tranformer, 1));
            }
            $field->addViewTransformer($tranformer);
        } elseif (array_has($info, 'transformer.model')) {
            $tranformer = array_get($info, 'transformer.model');
            if (is_string($tranformer)) {
                $tranformer = $this->container->get(substr($tranformer, 1));
            }
            $field->addModelTransformer($tranformer);
        }

        return $field;
    }

    private function generateDropdown(string $name, array $info = []): FormBuilderInterface
    {
        $choices = [];

        if (array_has($info, 'choicesFrom')) {
            if (isset($info['choicesFrom']['preselect']) && $info['choicesFrom']['preselect'] === false) {
                $choices['Nothing selected'] = null;
            }

            $expressionLanguage = new ExpressionLanguage();
            $label = array_get($info, 'choicesFrom.label');
            $value = array_get($info, 'choicesFrom.value');

            $result = $this->getResultFromFunction(array_get($info, 'choicesFrom.function'), array_get($info, 'choicesFrom.arguments', []));
            if (array_has($info, 'choicesFrom.from')) {
                $result = $result[array_get($info, 'choicesFrom.from')];
            }

            foreach ($result as $record) {
                $choices[$expressionLanguage->evaluate($label, ['result' => $record])] = $expressionLanguage->evaluate($value, ['result' => $record]);
            }
        } else {
            $choices = array_get($info, 'options.choices', []);
        }
        $options = $info['options'] ?? [];

        if ($info['isAjax'] ?? false) {
            $options['choice_loader'] = new \AppBundle\Component\DynamicChoiceLoader();
        } else {
            if ($name === 'country') {
                $options['choices'] = array_filter($choices, function($choice) {
                    return $choice !== null;
                });
                $options['empty_data'] = null;
                $options['required'] = false;
                $options['placeholder'] = 'Unknown';
            } else {
                $options['choices'] = $choices;
            }
        }

        $field = $this->getFormFactory()->createNamedBuilder($name, ChoiceType::class, null, $options);

        $field->setAttribute('size', $info['size'] ?? 12);
        $field->setAttribute('fieldProperties', $info);

        if (array_has($info, 'transformer.view')) {
            $tranformer = array_get($info, 'transformer.view');
            if (is_string($tranformer)) {
                $tranformer = $this->container->get(substr($tranformer, 1));
            }
            $field->addViewTransformer($tranformer);
        } elseif (array_has($info, 'transformer.model')) {
            $tranformer = array_get($info, 'transformer.model');

            if (is_string($tranformer)) {
                $tranformer = $this->container->get(substr($tranformer, 1));
            }
            $field->addModelTransformer($tranformer);
        }

        return $field;
    }

    private function generateChoices(string $name, array $info = []): FormBuilderInterface
    {
        return $this->generateDropdown($name, $info);
    }

    private function generateDate(string $name, array $info = []): FormBuilderInterface
    {
        $field = $this->getFormFactory()->createNamedBuilder($name, DateType::class, null, $info['options'] ?? []);
        $field->setAttribute('size', $info['size'] ?? 12);
        $field->setAttribute('fieldProperties', $info);

        return $field;
    }

    private function generateDatetime(string $name, array $info = []): FormBuilderInterface
    {
        $field = $this->getFormFactory()->createNamedBuilder($name, DateTimeType::class, null, $info['options'] ?? []);
        $field->setAttribute('size', $info['size'] ?? 12);
        $field->setAttribute('fieldProperties', $info);

        return $field;
    }

    private function generatePassword(string $name, array $info = []): FormBuilderInterface
    {
        $field = $this->getFormFactory()->createNamedBuilder($name, PasswordType::class, null, $info['options'] ?? []);
        $field->setAttribute('size', $info['size'] ?? 12);
        $field->setAttribute('fieldProperties', $info);

        return $field;
    }

    private function generateEmail(string $name, array $info = []): FormBuilderInterface
    {
        $field = $this->getFormFactory()->createNamedBuilder($name, EmailType::class, null, $info['options'] ?? []);
        $field->setAttribute('size', $info['size'] ?? 12);
        $field->setAttribute('fieldProperties', $info);

        return $field;
    }

    private function generateSwitch(string $name, array $info = []): FormBuilderInterface
    {
        $field = $this->getFormFactory()->createNamedBuilder($name, SwitchType::class, null, $info['options'] ?? []);
        $field->setAttribute('size', $info['size'] ?? 12);
        $field->setAttribute('fieldProperties', $info);

        return $field;
    }

    private function generateButton(string $name, array $info = []): FormBuilderInterface
    {
        $field = $this->getFormFactory()->createNamedBuilder($name, ButtonType::class, null, $info['options'] ?? []);
        $field->setAttribute('size', $info['size'] ?? 12);
        $field->setAttribute('fieldProperties', $info);

        return $field;
    }

    private function generateSubmit(string $name, array $info = []): FormBuilderInterface
    {
        $field = $this->getFormFactory()->createNamedBuilder($name, SubmitType::class, null, $info['options'] ?? []);
        $field->setAttribute('size', $info['size'] ?? 12);
        $field->setAttribute('fieldProperties', $info);

        return $field;
    }

    private function addTransformer(FormBuilderInterface $builder, string $name = null): void
    {
        $propertyPath = 'transformer';
        if ($name !== null) {
            $propertyPath = 'fields.' . $name . '.transformer';
        }

        if ($this->property($propertyPath .'.view', null) !== null) {
            $viewTransformCallable = $this->getCallable($this->property($propertyPath .'.view.transform'));
            $viewReverseTransformCallable = $this->getCallable($this->property($propertyPath .'.view.reverseTransform'));
            $builder->get($name)->addViewTransformer(new CallbackTransformer($viewTransformCallable, $viewReverseTransformCallable));
        }

        if ($this->property($propertyPath .'.model', null) !== null) {
            $modelTransformCallable = $this->getCallable($this->property($propertyPath .'.model.transform'));
            $modelReverseTransformCallable = $this->getCallable($this->property($propertyPath .'.model.reverseTransform'));
            $builder->get($name)->addModelTransformer(new CallbackTransformer($modelTransformCallable, $modelReverseTransformCallable));
        }
    }

    private function getTemplateFormRenderer(): FormRendererInterface
    {
        return $this->container->get('twig.form.renderer');
    }

    private function getRouter(): Router
    {
        return $this->container->get('router');
    }

    private function getRepository(string $class): EntityRepository
    {
        return $this->container->get('doctrine')->getRepository($class);
    }

    private function jsonResponse($data, int $status = 200, array $headers = [], ?SerializationContext $context = null)
    {
        if ($this->container->has('jms_serializer')) {
            $json = $this->container->get('jms_serializer')->serialize($data, 'json', $context);

            return new JsonResponse($json, $status, $headers, true);
        }

        return new JsonResponse($data, $status, $headers);
    }

    private function getEntityManager(string $name = 'default'): EntityManager
    {
        return $this->container->get('doctrine')->getManager($name);
    }
}
