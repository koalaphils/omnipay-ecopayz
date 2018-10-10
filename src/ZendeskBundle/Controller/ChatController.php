<?php

namespace ZendeskBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class ChatController extends AbstractController
{
    public function ssoZopimAction()
    {
        $ssoUri = $this->getContainer()->getParameter('zopim_sso');
        $response = $this->getManager()->getAPI()->guzzle->post($ssoUri, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getContainer()->getParameter('zendesk_security_options')['token'],
            ],
        ]);
        $data = \GuzzleHttp\json_decode($response->getBody()->getContents());

        return new JsonResponse($data->authorization);
    }

    /**
     * Get view manager.
     *
     * @return \ZendeskBundle\Manager\APIManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('zendesk.api_manager');
    }
}
