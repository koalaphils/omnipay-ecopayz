<?php

namespace AppBundle\Controller;

use AppBundle\Manager\PageManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;

class PageController extends AbstractController
{
    public function withWidgetAction(Request $request, string $action, array $widgets = []): Response
    {
        $attributes = $request->attributes;
        $params = $request->get('_route_params');
        $pageManager = $this->getManager();
        $pageManager->setController($this);
        $pageManager->setAction($action);
        $pageManager->addFromArray($action, $widgets);
        $response = '';

        //$this->getAssetManager()->addJs($this->getAssets('bundles/app/js/Page.js'));
        if (method_exists($this, camel_case($action . '_getConfig'))) {
            $configs = call_user_func([$this, camel_case($action . '_getConfig')]);
        } else {
            $configs = $this->getConfigs($attributes, $action);
        }

        $pageManager->setConfigs($configs);

        if (isset($configs['widgets'])) {
            foreach ($configs['widgets'] as $widgetName => $widgetInfo) {
                $pageManager->add($action, $widgetName, $widgetInfo['type'], $widgetInfo['properties']);
            }
        }

        if ($request->isXmlHttpRequest() && $request->headers->has('X-WIDGET-REQUEST'))
        {
            $widgetRequest = $request->headers->get('X-WIDGET-REQUEST');
            if ($request->headers->has('X-WIDGET-DATA')) {
                $requestData = array_merge($request->request->get($request->headers->get('X-WIDGET-DATA'), []), $request->query->get($request->headers->get('X-WIDGET-DATA'), []));
            } else {
                $requestData = array_merge($request->request->get('data', []), $request->query->get('data', []));
            }
            if (method_exists($this, camel_case($action . '_' . $widgetRequest))) {
                $response = call_user_func([$this, camel_case($action . '_' . $widgetRequest)], $pageManager, $requestData);
            } elseif (method_exists($this, $widgetRequest)) {
                $response = call_user_func([$this, $widgetRequest], $pageManager, $requestData);
            } else {
                $properties = array_merge($request->request->get('properties', []), $request->query->get('properties', []));
                $widget = $pageManager->findWidgetByPath($request->headers->get('X-WIDGET-PATH'), $properties);
                if ($widget === null) {
                    throw $this->createNotFoundException('Unable to found widget');
                }

                $widgetAction = camel_case($widget->getFullId() . '_' . $widgetRequest);
                if (method_exists($this, $widgetAction)) {
                    $response = call_user_func_array([$this, $widgetAction], [$pageManager, $widget, $requestData]);
                } elseif (method_exists($widget, $widgetRequest)) {
                    $response = call_user_func([$widget, $widgetRequest], $requestData);
                } else {
                    throw $this->createNotFoundException('No action');
                }
            }
        } elseif (method_exists($this, $action . 'Action')) {
            $response = call_user_func_array([$this, $action . 'Action'], [$action, $request, $pageManager]);
        } elseif ($pageManager->getConfig('template', null) !== null) {
            $response = $this->defaultAction($action, $request, $pageManager);
        } else {
            throw $this->createNotFoundException('No action');
        }

        if ($response instanceof Response) {
            return $response;
        } elseif (is_string($response)) {
            return new Response($response);
        } elseif (is_array($response)) {
            if (array_key_exists('code', $response)) {
                return $this->jsonResponse($response, $response['code']);
            } else {
                return $this->jsonResponse($response);
            }
        } else {
            throw new \RuntimeException('Not supported format');
        }
    }

    public function defaultAction(string $action, Request $request, \AppBundle\Manager\PageManager $pageManager)
    {
        if (!empty($pageManager->getConfig('roles', []))) {
            $this->denyAccessUnlessGranted($pageManager->getConfig('roles', []));
        }

        if (method_exists($this, $action . '_initPage')) {
            call_user_func([$this, $action], $request, $pageManager);
        }

        $viewParameters = $pageManager->getAllData();
        $viewParameters['page'] = $pageManager;
        $viewParameters['widgets'] = $pageManager->getWidgets();

        return $this->render($pageManager->getConfig('template'), $viewParameters);
    }

    protected function getManager(): PageManager
    {
        return $this->get('app.page_manager');
    }

    protected function getKernel(): Kernel
    {
        return $this->get('kernel');
    }

    protected function getConfigs(\Symfony\Component\HttpFoundation\ParameterBag $attributes, string $action): array
    {
        $controller = $attributes->get('_controller');
        $controllerPart = explode('\\', $controller);
        $bundleName = '';
        $controllerName = '';
        foreach ($controllerPart as $part) {
            if (substr($part, -6) === 'Bundle') {
                $bundleName = $part;
            } else {
                $actionPart = explode('::', $part);
                if (count($actionPart) == 2 && substr($actionPart[0], -10) === 'Controller') {
                    $controllerName = strtolower(substr($actionPart[0], 0, strlen($actionPart[0]) - 10));
                }
            }
        }
        if ($bundleName !== '' && $controllerName !== '') {
            try {
                $file = $this->getKernel()->locateResource('@' . $bundleName . '/Resources/config/pages/' . $controllerName . '/' . $action . '_config.yml');
                $configs = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file), \Symfony\Component\Yaml\Yaml::PARSE_CONSTANT);
                if ($configs === null) {
                    return [];
                }

                return $configs;
            } catch (\InvalidArgumentException $ex) {
                return [];
            }
        }

        return [];
    }

    private function getAssetManager(): \AppBundle\Manager\AssetManager
    {
        return $this->container->get('app.asset_manager');
    }

    private function getAssets($path): string
    {
        return $this->container->get('assets.packages')->getUrl($path);
    }
}
