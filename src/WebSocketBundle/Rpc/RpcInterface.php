<?php

namespace WebSocketBundle\Rpc;

interface RpcInterface
{
    public function getName();

    public function onCall();
}
