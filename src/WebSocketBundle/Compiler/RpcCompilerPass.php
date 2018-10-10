<?php

namespace WebSocketBundle\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class RpcCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('websocket.rpc_manager')) {
            return;
        }

        $definition = $container->findDefinition('websocket.rpc_manager');
        $taggedServices = $container->findTaggedServiceIds('websocket.rpc');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $attr = $attributes;
                unset($attr['uri']);
                unset($attr['method']);
                unset($attr['event']);
                $definition->addMethodCall(
                    'addRpc',
                    [
                        new Reference($id),
                        $attributes['uri'],
                        array_key_exists('method', $attributes) ? $attributes['method'] : 'onCall',
                        array_key_exists('event', $attributes) ? $attributes['event'] : null,
                        array_key_exists('then', $attributes) ? $attributes['then'] : null,
                        $attributes,
                    ]
                );
            }
        }
    }
}
