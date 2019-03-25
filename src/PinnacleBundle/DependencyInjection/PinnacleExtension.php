<?php

declare(strict_types = 1);

namespace PinnacleBundle\DependencyInjection;

use PinnacleBundle\Service\PinnacleService;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class PinnacleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $pinnacleServiceDefinition = new Definition();
        $pinnacleServiceDefinition->setAutoconfigured(true);
        $pinnacleServiceDefinition->setAutowired(true);
        $pinnacleServiceDefinition->setPublic(false);
        $pinnacleServiceDefinition->setClass(PinnacleService::class);
        $pinnacleServiceDefinition->setArguments([
            '$apiUrl' => $config['api']['url'],
            '$agentCode' => $config['agent_code'],
            '$agentKey' => $config['api']['agent_key'],
            '$secretKey' => $config['api']['secret_key'],
        ]);

        $container->setDefinition(PinnacleService::class, $pinnacleServiceDefinition);
    }
}