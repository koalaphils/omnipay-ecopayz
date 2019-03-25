<?php

declare(strict_types = 1);

namespace TwoFactorBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use TwoFactorBundle\Provider\TwoFactorRegistry;

class TwoFactorProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(TwoFactorRegistry::class)) {
            return;
        }

        $definition = $container->getDefinition(TwoFactorRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('2fa.provider');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall('addProvider', [new Reference($id), $attributes["alias"]]);
            }
        }
    }
}