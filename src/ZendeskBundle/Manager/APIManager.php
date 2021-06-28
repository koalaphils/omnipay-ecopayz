<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ZendeskBundle\Manager;

use Zendesk\API\HttpClient as ZendeskAPI;

class APIManager
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;
    /**
     * @var ZendeskAPI
     */
    protected $api;

    public function __construct($subdomain, $username = '')
    {
        $this->api = new ZendeskAPI($subdomain, $username);
    }

    public function setAuth($type, $options)
    {
        $this->getAPI()->setAuth($type, $options);

        return $this;
    }

    public function setApiBase($apiBasePath)
    {
        $this->getAPI()->setApiBasePath($apiBasePath);

        return $this;
    }

    /**
     * @return ZendeskAPI
     */
    public function getAPI()
    {
        return $this->api;
    }

    /**
     * Get User.
     *
     * @return \DbBundle\Entity\User
     *
     * @throws \LogicException
     */
    public function getUser()
    {
        if (!$this->getContainer()->has('security.token_storage')) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->getContainer()->get('security.token_storage')->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return;
        }

        return $user;
    }

    /**
     * Get Session.
     *
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    protected function getSession()
    {
        return $this->getContainer()->get('session');
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }
}
