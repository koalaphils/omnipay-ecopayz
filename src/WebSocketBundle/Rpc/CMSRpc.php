<?php

namespace WebSocketBundle\Rpc;

use AppBundle\Manager\SettingManager;
use WebSocketBundle\Security\AuthorizationManager;
use WebSocketBundle\Security\JwtAuthProvider;

class CMSRpc implements RpcInterface
{
    private $authManager;
    private $jwtAuthProvider;
    private $settingManager;

    public function __construct(
        SettingManager $settingManager,
        AuthorizationManager $authManager,
        JwtAuthProvider $jwtAuthProvider
    ) {
        $this->authManager = $authManager;
        $this->jwtAuthProvider = $jwtAuthProvider;
        $this->settingManager = $settingManager;
    }

    public function getName()
    {
        return 'webscoket.cms_rpc';
    }

    public function getCounter()
    {
        if ($this->_getJwtAuthProvider()->getCounter(null) === null) {
            $counter = $this->_getSettingManager()->getSetting('counter');
            $this->_getJwtAuthProvider()->setCounter(null, $counter);
        }

        return [$this->_getJwtAuthProvider()->getCounter(null)];
    }

    public function setCounter($args)
    {
        $counter = $args[0];
        $value = $args[1];

        if ($this->_getJwtAuthProvider()->getCounter(null) === null) {
            $value = $this->_getSettingManager()->getSetting('counter');
            $this->_getJwtAuthProvider()->setCounter(null, $value);
        } else {
            $this->_getJwtAuthProvider()->setCounter($counter, $value);
        }

        return [$this->_getJwtAuthProvider()->getCounter(null)];
    }

    public function updateCounter($args)
    {
        foreach ($args as $counterKey => $value) {
            $old = $this->_getJwtAuthProvider()->getCounter($counterKey, 0);
            $new = $old + $value;
            $this->_getJwtAuthProvider()->setCounter($counterKey, $new);
        }

        return [$this->_getJwtAuthProvider()->getCounter(null)];
    }

    public function onUpdateCounter(\Thruway\Peer\Client $client, \Thruway\Event\MessageEvent $event)
    {
        $client->getSession()->publish('counter', [], $this->_getJwtAuthProvider()->getCounter(null));
    }

    public function onCall()
    {
        return [];
    }

    /**
     * Get Setting Manager.
     *
     * @return \AppBundle\Manager\SettingManager
     */
    protected function _getSettingManager()
    {
        return $this->settingManager;
    }

    /**
     * Get jwt auth provider.
     *
     * @return \WebSocketBundle\Security\JwtAuthProvider
     */
    protected function _getJwtAuthProvider()
    {
        return $this->jwtAuthProvider;
    }

    /**
     * @return \WebSocketBundle\Security\AuthorizationManager
     */
    protected function _getAuthorizationManager()
    {
        return $this->authManager;
    }
}
