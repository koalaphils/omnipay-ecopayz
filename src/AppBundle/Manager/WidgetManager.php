<?php

namespace AppBundle\Manager;

use AppBundle\Exceptions\DuplicateNameWidgetException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Interfaces\WidgetInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WidgetManager
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    private $widgets = [];

    public function addWidget(string $id, string $class, string $name, bool $forDashboard = true)
    {
        $this->widgets[$name] = [
            'reference' => $id,
            'class' => $class,
            'details' => call_user_func([$class, 'defineDetails']),
            'properties' => call_user_func([$class, 'defineProperties'], $this->container),
            'dashboard' => $forDashboard,
        ];
    }

    public function guardWidget(string $name, string $class)
    {
        if (array_has($this->widgets, $name)) {
            throw new DuplicateNameWidgetException();
        }

        if (!class_exists($class)) {
            throw new LogicException('Class not found');
        }

        if (!method_exists($class, 'defineDetails')) {
            throw new LogicException(sprintf('Method defineDetails not exists in %s', $class));
        }
    }

    public function getWidget(string $widgetName, array $properties = [], array $definition = []): \AppBundle\Widget\AbstractWidget
    {
        $widget = $this->getWidgetDefinition($widgetName);

        $widgetService = $this->container->get($widget['reference']);
        if (array_has($definition, 'id')) {
            $widgetService->setId($definition['id']);
        }

        if (array_has($definition, 'full_id')) {
            $widgetService->setId($definition['id']);
        }

        if (array_has($definition, 'inheritTemplate')) {
            $widgetService->setInheritTemplatePath($definition['inheritTemplate']);
        }

        $widgetService->setProperties($properties);
        $widgetService->init();
        $widgetService->run();

        return $widgetService;
    }

    public function getWidgets(): array
    {
        return $this->widgets;
    }

    public function getDashboardWidgets(): array
    {
        $widgets = [];
        foreach ($this->widgets as $name => $widget) {
            if ($widget['dashboard']) {
                $widgets[$name] = $widget;
            }
        }

        return $widgets;
    }

    public function hasWidget(string $widgetName): bool
    {
        return array_has($this->widgets, $widgetName);
    }

    public function getWidgetForm(string $widgetName, array $data = [], array $definition = [], FormBuilder $formBuilder = null): \Symfony\Component\Form\FormInterface
    {
        if ($formBuilder === null) {
            $formBuilder = $this->getFormFactory()->createNamedBuilder('widget', FormType::class, $data, [
                'csrf_protection' => false,
                'allow_extra_fields' => true,
            ]);
        }

        $widget = $this->getWidget($widgetName, $data, $definition);
        $widget->buildForm($formBuilder);

        return $formBuilder->getForm();
    }

    public function getWidgetDefinition(string $widgetName)
    {
        if (!$this->hasWidget($widgetName)) {
            throw new \LogicException(sprintf('Widget "%s" not exists', $widgetName));
        }

        return $this->widgets[$widgetName];
    }

    public function setInheritTemplate(\AppBundle\Widget\AbstractWidget $widget, string $template)
    {
        $widget->setInheritTemplatePath($template);
    }

    public function onActionWidget(WidgetInterface $widget, string $action, array $data)
    {
        if ($action === null) {
            throw new \LogicException('You must define Action', 405);
        } elseif (!method_exists($widget, $action)) {
            throw new NotFoundHttpException('Action not found');
        }

        return call_user_func([$widget, $action], $data);
    }

    private function getFormFactory(): \Symfony\Component\Form\FormFactory
    {
        return $this->container->get('form.factory');
    }
}
