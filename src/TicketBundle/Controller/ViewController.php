<?php

namespace TicketBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Zendesk\API\Exceptions\ApiResponseException;

/**
 * Description of ViewController.
 *
 * @author cnonog
 */
class ViewController extends AbstractController
{
    public function listAction(Request $request)
    {
        try {
            $result = $this->getManager()->getList($request);
            $result['views'] = array_map(function ($data) {
                $data->url = [
                    'execute' => $this->getRouter()->generate('ticket.view_execute', ['id' => $data->id]),
                ];

                return $data;
            }, $result['views']);
        } catch (ApiResponseException $e) {
            return new JsonResponse(json_decode($e->getErrorDetails(), true), $e->getCode());
        }

        return new JsonResponse($result);
    }

    public function executeAction(Request $request, $id)
    {
        try {
            $result = $this->getManager()->execute($request, $id);
            if ($request->get('datatable', 0)) {
                $result->draw = $request->get('draw');
            }
        } catch (ApiResponseException $e) {
            return new JsonResponse(json_decode($e->getErrorDetails(), true), $e->getCode());
        }

        return new JsonResponse($result);
    }

    /**
     * Get view manager.
     *
     * @return \ZendeskBundle\Manager\ViewManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('zendesk.view_manager');
    }
}
