<?php

namespace WebSocketBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use WebSocketBundle\Compiler\RpcCompilerPass;
use WebSocketBundle\Compiler\TopicCompilerPass;
use WebSocketBundle\Compiler\OverrideCompilerPass;

class WebSocketBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        //$container->addCompilerPass(new OverrideCompilerPass());
        //$container->addCompilerPass(new RpcCompilerPass());
        //$container->addCompilerPass(new TopicCompilerPass());
    }


}
