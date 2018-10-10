<?php

namespace ZendeskBundle\Controller;

use AppBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Zendesk\API\Utilities\OAuth;

class DefaultController extends AbstractController
{
    public function authorizeAction(Request $request)
    {
        $state = unserialize(base64_decode($request->get('state')));
        $params['code'] = $request->get('code');
        $params['client_id'] = $this->getContainer()->getParameter('zendesk_security_options')['client_id'];
        $params['client_secret'] = $this->getContainer()->getParameter('zendesk_security_options')['client_secret'];
        $params['redirect_uri'] = $request->getSchemeAndHttpHost()
            . $this->getRouter()->generate('zendesk.authorize', ['_locale' => $request->getLocale()]);
        $response = OAuth::getAccessToken($this->getManager()->getAPI()->guzzle, $this->getManager()->getAPI()->getSubdomain(), $params);

        $response = \ZendeskBundle\Adapter\ZendeskAdapter::create($response);

        $preferences = $this->getUser()->getPreferences();
        array_set($preferences, 'zendesk_token', $response->getAccessToken());
        $this->getUser()->setPreferences($preferences);
        $this->getUserRepository()->save($this->getUser());

        return $this->redirect($state['current_url']);
    }

    /**
     * Get user repository.
     *
     * @return \DbBundle\Repository\UserRepository
     */
    protected function getUserRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:User');
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
