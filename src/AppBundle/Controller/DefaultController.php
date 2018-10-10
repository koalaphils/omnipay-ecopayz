<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use AppBundle\Widget\QuotesWidget;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DefaultController extends AbstractController
{
    const HAS_DELETE = 1;

    public function indexAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        return $this->render(
            'AppBundle:Default:index.html.twig',
            ['base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR]
        );
    }

    public function indexOldAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

        $widgets = $this->getWidgetManager()->getDashboardWidgets();

        array_forget($widgets, ['collection', 'quotes']);
        $json = $this->getSettingManager()->getSetting('dashboard.default_widgets', []);
        $userWidgets = $this->getUser()->getPreference('dashboard.widgets', []);
        $hasDeleted = $request->getSession()->get('delete_widget');

        if(is_null($hasDeleted)){
            $widget= $json;
            $request->getSession()->set('default_widget',$widget);
            $defaultWidgets = $request->getSession()->get('default_widget');
        }else{
            $newWidget = $request->getSession()->get('newWidget');
            $request->getSession()->set('default_widget',$newWidget);
            $defaultWidgets = $request->getSession()->get('default_widget');
        }

        $userWidgets = $this->getUser()->getPreference('dashboard.widgets', []);

        $userWidgets = array_merge($defaultWidgets, $userWidgets);

        $dashboardWidgets = $this->getWidgetManager()->getWidget(
            'collection',
            ['size' => 12, 'type' => 'column', 'children' => $userWidgets],
            ['id' => 'dashboard_widgets']
        );

        if ($request->isXmlHttpRequest() && $request->headers->has('X-WIDGET-REQUEST') && $request->headers->has('X-WIDGET-ID')) {
            $requestData = $request->get('widget', []);
            $widget = $dashboardWidgets->findChildren($request->headers->get('X-WIDGET-PATH'));

            $properties = $widget->getProperties();
            $properties = array_merge($properties, $request->get('widget_property', []));

            if ($widget->getInteritTemplateRealValue() === null) {
                $definitions = ['id' => $widget->getId()];
            } else {
                $definitions = ['id' => $widget->getId(), 'inheritTemplate' => $widget->getInteritTemplateRealValue()];
            }

            $refactoredWidget = $this->getWidgetManager()->getWidget($widget->getType(), $properties, $definitions);
            $response = $this->getWidgetManager()->onActionWidget($refactoredWidget, $request->headers->get('X-WIDGET-REQUEST'), $requestData);

            if ($response instanceof Response) {
                return $response;
            } elseif (is_array($response)) {
                return $this->jsonResponse($response);
            }

            return $response;
        }

        return $this->render(
            'AppBundle:Default:dashboard.html.twig',
            [
                'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..') . DIRECTORY_SEPARATOR,
                'dashboardWidgets' => $dashboardWidgets,
                'widgets' => $widgets,
            ]
        );
    }

    public function renderDashboardWidgetFormAction(string $widgetName)
    {
        $widget = $this->getWidgetManager()->getWidgetDefinition($widgetName);
        $form = $this->createForm(\AppBundle\Form\WidgetType::class, null, [
            'action' => $this->getRouter()->generate('app.dashboard_widget_form_add', ['widgetName' => $widgetName]),
            'widget' => $widget,
        ]);

        return $this->render('AppBundle:Widget:form.html.twig', ['form' => $form->createView()]);
    }

    public function addWidgetAction(string $widgetName, Request $request)
    {
        $widget = $this->getWidgetManager()->getWidgetDefinition($widgetName);
        $form = $this->createForm(\AppBundle\Form\WidgetType::class, null, [
            'widget' => $widget,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $preferencesKey = $widgetName . '_' . date('YmdHis');
            $preference = [
                'type' => $widgetName,
                'properties' => $data,
                'definition' => ['id' => $preferencesKey],
            ];
            $this->getUserManager()->savePreferences('dashboard.widgets.' . $preferencesKey, $preference);

            return $this->json($preference);
        }

        $formValidation = new \AppBundle\Exceptions\FormValidationException($form);

        return $this->json($formValidation->getErrors(), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function removeWidgetAction(string $widgetId, Request $request)
    {
        $deleteWidget = $request->getSession()->get('default_widget');
        unset($deleteWidget[$widgetId]);
        $request->getSession()->set('newWidget',$deleteWidget);
        $request->getSession()->set('delete_widget',self::HAS_DELETE);
        $this->getUserManager()->removePreferences('dashboard.widgets.' . $widgetId);

        return $this->json(['message' => 'Widget successfully deleted']);
    }

    public function redirectAction(Request $request)
    {
        $locale = $request->cookies->get('_locale', $request->getLocale());
        $path = '/' . $locale . $request->getPathInfo();

        $route = $this->getRouter()->match($path);
        if ($route['_route'] === 'root') {
            throw $this->createNotFoundException(sprintf('No route found for "%s %s"', $request->getMethod(), $request->getPathInfo()));
        }

        return $this->redirect($path);
    }

    public function renderFileAction(Request $request, $fileName = '')
    {
        if ($this->has('profiler')) {
            $this->get('profiler')->disable();
        }

        try {
            $relativePath = $this->getMediaManager()->renderFile($fileName, $request->get('folder'));

            return new BinaryFileResponse($relativePath);
        } catch (FileNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }
    }

    public function makeWidgetsDefaultAction(Request $request)
    {
        $this->denyAccessUnlessGranted(['ROLE_SUPER_ADMIN']);

        $userWidgets = $this->getUser()->getPreference('dashboard.widgets', []);
        $userWidgets = array_map(function ($userWidget) {
            $userWidget['definition']['default'] = true;

            return $userWidget;
        }, $userWidgets);

        $this->getSettingManager()->saveSetting('dashboard.default_widgets', $userWidgets);
        $request->getSession()->set('default_widgets',$userWidgets);

        return $this->json(['message' => 'Successfully saved default ']);
    }

    protected function checkIfFileInUploadFolder($uploadFolder, $relativePath): bool
    {
        $fileRealPath = realpath($relativePath);
        $explodedFolder = explode(DIRECTORY_SEPARATOR, $uploadFolder);
        $explodedRealPath = explode(DIRECTORY_SEPARATOR, $fileRealPath, count($explodedFolder));

        $uploadDirPath = implode(DIRECTORY_SEPARATOR, $explodedFolder);
        $filePath = implode(DIRECTORY_SEPARATOR, $explodedRealPath);
        $beginningOfString = 0;
        
        return  (strpos($filePath, $uploadDirPath) === $beginningOfString);
    }

    public function getNotificationListAction()
    {
        $response = $this->getNotificationManager()->getList();

        return new JsonResponse($response);
    }

    public function saveNotificationLastReadAction()
    {
        $response = $this->getNotificationManager()->saveLastRead();

        return new JsonResponse($response);
    }

    protected function getManager()
    {
    }

    private function getUserManager(): \UserBundle\Manager\UserManager
    {
        return $this->get('user.manager');
    }

    private function getNotificationManager(): \AppBundle\Manager\NotificationManager
    {
        return $this->get('app.notification_manager');
    }

    private function getMediaManager(): \MediaBundle\Manager\MediaManager
    {
        return $this->get('media.manager');
    }
}
