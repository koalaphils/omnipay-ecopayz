<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WidgetController extends AbstractController
{
    public function widgetRenderAction(string $widgetName)
    {
        $widget = $this->getManager()->getWidget($widgetName, $this->getCurrentRequest()->get('properties', []));
        $widgetRender = $widget->render();
        if ($widgetRender instanceof Response) {
            return $widgetRender;
        }

        return new Response($widgetRender);
    }

    public function widgetOnAjaxAction(string $widgetName)
    {
        $widget = $this->getManager()->getWidget($widgetName);
        $action = $this->getCurrentRequest()->headers->get('X-WIDGET-REQUEST', null);

        if ($action === null) {
            return new Response('You must define request', 405);
        }

        if (!method_exists($widget, $action)) {
            return new Response('Not found action', 404);
        }

        return call_user_func([$widget, $action], [$this->getCurrentRequest()->request->all()]);
    }

    public function renderWidgetFormAction(string $widgetName)
    {
        $definitions = [];
        if ($this->getCurrentRequest()->headers->has('X-WIDGET-ID')) {
            $definitions['id'] = $this->getCurrentRequest()->headers->get('X-WIDGET-ID');
        }
        if ($this->getCurrentRequest()->headers->has('X-WIDGET-INHERIT-TEMPLATE')) {
            $definitions['inheritTemplate'] = $this->getCurrentRequest()->headers->get('X-WIDGET-INHERIT-TEMPLATE');
        }
        $form = $this->getManager()->getWidgetForm($widgetName, $this->getCurrentRequest()->get('properties', []), $definitions);

        return $this->render('AppBundle:Widget:form.html.twig', ['form' => $form->createView()]);
    }

    private function getCurrentRequest(): Request
    {
        return $this->getContainer()->get('request_stack')->getCurrentRequest();
    }

    protected function getManager(): \AppBundle\Manager\WidgetManager
    {
        return $this->getWidgetManager();
    }
}
