<?php

namespace AppBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DataTransferCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('app.component.data_transfer')) {
            return;
        }

        $definition = $container->findDefinition('app.component.data_transfer');

        $taggedServices = $container->findTaggedServiceIds('app.data_transfer');
        $services = [];
        foreach ($taggedServices as $id => $tag) {
            $serviceDefinition = $container->getDefinition($id);
            $class = $serviceDefinition->getClass();
            $services[$class] = $id;
        }

        $definition->addMethodCall('register', [$services]);
    }
}
