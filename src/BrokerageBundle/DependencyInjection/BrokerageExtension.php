<?php

namespace BrokerageBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class BrokerageExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if ($container->hasDefinition('brokerage.brokerage_service')) {
            $definition = $container->getDefinition('brokerage.brokerage_service');
            $definition->addMethodCall('setBrokerageUrl', [$config['url']]);
            $definition->addMethodCall('setAccessToken', [$config['security']['token']]);
            $definition->addMethodCall('setTokenType', [$config['security']['token_type']]);
        }
    }
}
