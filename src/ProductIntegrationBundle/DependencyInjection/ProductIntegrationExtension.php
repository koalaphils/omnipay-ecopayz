<?php

namespace ProductIntegrationBundle\DependencyInjection;

use ProductIntegrationBundle\Persistence\HttpPersistence;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;

class ProductIntegrationExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);
        $integrations = $config['integrations'];

        // Dynamically create Service Definitions for each integrations.
        $factoryParameters = []; // Will be used by the factory
        foreach ($integrations as $key => $integration) {
            $arguments = $this->resolveArguments($container, $integration);

            $integrationDefinition = new Definition($integration['class']);
            $integrationDefinition->setArguments($arguments);

            $container->setDefinition($integration['class'], $integrationDefinition);

            $factoryParameters[$key] = $integration['class']; 
        }

        $factoryDefinition = new Definition(ProductIntegrationFactory::class);
        $factoryDefinition->setArguments($factoryParameters);
        $container->setDefinition(ProductIntegrationFactory::class, $factoryDefinition);
    }

    private function resolveArguments($container, array $integration): array
    {
        $arguments = [];

        if (isset($integration['url'])) { 
            $httpPersistenceDefinition = new Definition(HttpPersistence::class);
            $httpPersistenceDefinition->setArguments([$integration['url']]);

            $serviceId = uniqid() . '_http';

            $container->setDefinition($serviceId, $httpPersistenceDefinition);
            $arguments[] = new Reference($serviceId);
        } else {
            $reflector = new \ReflectionClass($integration['class']);
            $reflectParams = $reflector->getConstructor()->getParameters();
            foreach ($reflectParams as $param) {
                $namedType = $param->getType();
                $arguments[] = new Reference($namedType->getName());
            }
        }

        return $arguments;
    }
}