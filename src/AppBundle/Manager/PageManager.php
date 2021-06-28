<?php

namespace AppBundle\Manager;

use AppBundle\Controller\PageController;
use AppBundle\Widget\AbstractPageWidget;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class PageManager
{
    use ContainerAwareTrait;

    private $widgetsInfo;
    private $widgets;
    private $configs;
    private $controller;
    private $data;
    private $appVariable;
    private $expressionLanguage;
    private $action;

    public $vars;

    public function __construct()
    {
        $this->widgets = [];
        $this->widgetsInfo = [];
        $this->configs;
        $this->data = [];
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    public function setConfigs(array $configs): void
    {
        $this->configs = $configs;
        $this->vars = $this->configs['vars'] ?? [];
    }

    public function getConfig(string $name, $default = null)
    {
        return array_get($this->configs, $name, $default);
    }

    public function config(string $name, $default = null)
    {
        return array_get($this->configs, $name, $default);
    }

    public function addFromArray(string $action, array $widgets): void
    {
        foreach ($widgets as $widgetName => $info) {
            $this->add($action, $widgetName, $info['type'], $info['properties']);
        }
    }

    public function getController(): PageController
    {
        return $this->controller;
    }

    public function setController(PageController $controller): void
    {
        $this->controller = $controller;
    }

    public function add(string $action, string $widgetName, string $widgetType, array $properties = []): void
    {
        $this->widgetsInfo[$widgetName] = [
            'type' => $widgetType,
            'action' => $action,
            'properties' => $properties,
        ];
    }

    public function getWidget(string $widgetName, array $properties = []): AbstractPageWidget
    {
        if (!array_has($this->widgets, $widgetName)) {
            $this->widgets[$widgetName] = $this->generateWidget($widgetName, $properties);
        }

        return $this->widgets[$widgetName];
    }

    public function getWidgets(): array
    {
        foreach ($this->widgetsInfo as $name => $info) {
            $this->getWidget($name);
        }

        return $this->widgets;
    }

    public function getWidgetsInfo(): array
    {
        return $this->widgetsInfo;
    }

    public function findWidgetByPath(string $path, array $properties = []): AbstractPageWidget
    {
        $foundedWidget = null;
        foreach ($this->widgetsInfo as $widgetName => $info) {
            $widget = $this->getWidget($widgetName);
            $foundedWidget = $widget->findChildren($path);
            if ($foundedWidget != null) {
                break;
            }
        }

        return $foundedWidget;
    }

    public function getAllData(): array
    {
        $dataNames = array_keys($this->getConfig('data', []));
        foreach ($dataNames as $dataName) {
            $this->getData($dataName);
        }

        return $this->data;
    }

    public function getData(string $name)
    {
        if (!array_has($this->data, $name)) {
            $callback = [];
            $arguments = [];
            if (is_array($this->getConfig('data.' . $name))) {
                $callback = $this->getCallableFromArray($this->getConfig('data.' . $name));
                $arguments = $this->getConfig('data.' . $name . '.arguments', []);
                $this->processConfigValues($arguments);
            } else {
                $callback = $this->getCallable($this->getConfig('data.' . $name));
            }

            array_set($this->data, $name, call_user_func_array($callback, $arguments));
        }

        return array_get($this->data, $name, null);
    }

    public function getCallable(string $function): array
    {
        $action = $this->getAction();
        $controller = $this->getController();
        $result = [];

        list($class, $functionName) = explode('::', $function);
        if ($class === 'controller') {
            if (method_exists($controller, $action . '_' . $function)) {
                $result = [$controller, $action . '_' . $functionName];
            } else {
                $result = [$controller, $functionName];
            }
        } elseif (substr($class, 0, 1) === '@') {
            $service = $this->container->get(substr($class, 1));
            $result = [$service, $functionName];
        } else {
            $result = [$class, $functionName];
        }

        return $result;
    }

    public function getCallableFromArray(array $callableInfo): array
    {
        if (array_has($callableInfo, 'repositoryEntity')) {
            $repository = $this->container->get('doctrine')->getRepository($callableInfo['repositoryEntity']);

            return [$repository, $callableInfo['method']];
        }

        $class = $callableInfo['class'];
        $method = $callableInfo['method'];

        return $this->getCallable($class . '::' . $method);
    }

    public function initAppVariable(): void
    {
        $this->appVariable = new AppVariable();
        $this->appVariable->setRequestStack($this->container->get('request_stack'));
    }

    public function processConfigValues(array &$values): void
    {
        foreach ($values as &$value) {
            if (is_array($value)) {
                $this->processConfigValues($value);
            } else {
                $this->processConfigValue($value);
            }
        }
    }

    public function processConfigValue(&$value): void
    {
        if (substr($value, 0, 2) === '@=') {
            $value = $this->expressionLanguage->evaluate(substr($value, 2), [
                'app' => $this->getAppVariable(),
                'container' => $this->container,
                'parameter' => function ($param) {
                    return $this->container->getParameter($param);
                },
                'pageManager' => $this,
            ]);
        }
    }

    public function getExpressionLanguage(): ExpressionLanguage
    {
        return $this->expressionLanguage;
    }

    private function getAppVariable(): AppVariable
    {
        return $this->appVariable;
    }

    private function generateWidget(string $widgetName, array $properties = []): AbstractPageWidget
    {
        $widgetInfo = $this->widgetsInfo[$widgetName];
        $widget = new $widgetInfo['type']($widgetInfo['action'], $widgetName, $this);
        $widget->setContainer($this->container);
        $widget->setProperties(array_merge($widgetInfo['properties'], $properties));
        $widget->init();
        $widget->run();

        return $widget;
    }

    private function getAction(): string
    {
        return $this->action;
    }

    public function getCurrentRequest()
    {
        $requestStack = $this->container->get('request_stack');

        return $requestStack->getCurrentRequest();
    }
}
