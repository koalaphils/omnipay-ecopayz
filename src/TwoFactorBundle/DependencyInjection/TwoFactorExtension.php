<?php

declare(strict_types = 1);

namespace TwoFactorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use TwoFactorBundle\Provider\Message\Sms\SmsMessengerInterface;
use TwoFactorBundle\Provider\Message\StorageInterface;

class TwoFactorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('2fa.email_template', $config['messenger']['email']['template']);
        $container->setParameter('2fa.sms_template', $config['messenger']['sms']['template']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setAlias(SmsMessengerInterface::class, $config['messenger']['sms']['sms_messenger_service']);
        $container->setAlias(StorageInterface::class, $config['storage']);
    }
}