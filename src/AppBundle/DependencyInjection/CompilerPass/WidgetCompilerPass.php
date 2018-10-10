<?php

namespace AppBundle\DependencyInjection\CompilerPass;

use AppBundle\Manager\WidgetManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class WidgetCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('app.widget_manager')) {
            return;
        }

        $definition = $container->findDefinition('app.widget_manager');

        $taggedServices = $container->findTaggedServiceIds('app.widget');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall('addWidget', [$id, $container->findDefinition($id)->getClass(), $attributes['widget_name'] ?? null, $attributes['dashboard'] ?? true]);
            }
        }
    }
}
