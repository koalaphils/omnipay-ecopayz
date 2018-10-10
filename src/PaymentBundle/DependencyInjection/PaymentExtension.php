<?php

namespace PaymentBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class PaymentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $gatewayForms = $config['gateway_forms'] ?? [];

        foreach ($gatewayForms as $key => $gatewayForm) {
            $this->addGatewayForm($key, $gatewayForm, $container);
        }
    }

    public function addGatewayForm($mode, array $gatewayForm, ContainerBuilder $container)
    {
        $manager = $container->getDefinition('payment.gateway_form_manager');
        $manager->addMethodCall('addFormConfiguration', [$mode, $gatewayForm]);
    }
}
