<?php

namespace WebSocketBundle\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class OverrideCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('voryx.thruway.ratchet.transport');
        //$definition->setClass(\WebSocketBundle\Transport\WebSocketTransportProvider::class);
    }
}
