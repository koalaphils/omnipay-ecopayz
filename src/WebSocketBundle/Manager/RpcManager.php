<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace WebSocketBundle\Manager;

/**
 * Description of RpcManager.
 *
 * @author cnonog
 */
class RpcManager
{
    protected $rpcs;

    public function __construct()
    {
        $this->rpcs = [];
    }

    public function addRpc($rpc, $uri, $method, $event = null, $then = null)
    {
        $this->rpcs[$uri] = ['class' => $rpc, 'method' => $method, 'event' => $event, 'then' => $then];
    }

    public function getRpc($uri = null)
    {
        if ($uri === null) {
            return $this->rpcs;
        }
        if (array_has($this->rpcs, $uri)) {
            return $this->rpcs[$uri];
        }
        
        return null;
    }
}
