<?php

namespace WebSocketBundle\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class TopicCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('websocket.topic_manager')) {
            return;
        }

        $definition = $container->findDefinition('websocket.topic_manager');
        $taggedServices = $container->findTaggedServiceIds('websocket.topic');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall('addTopic', [
                    new Reference($id),
                    $attributes['uri'],
                ]);
            }
        }
    }
}
