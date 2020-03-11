<?php

namespace ApiBundle\ProductIntegration;

use DbBundle\Repository\CustomerProductRepository;
use PinnacleBundle\Service\PinnacleService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductIntegrationFactory
{
    private $config;
    private $pinnacleService;
    private $customerProductRepository;
    private $container;

    // TODO: All injected here can be removed once we made 
    // the integrations as services.
    public function __construct(array $config, 
        PinnacleService $pinnacleService,
        CustomerProductRepository $customerProductRepository,
        ContainerInterface $container) 
    {
        $this->config = $config;
        $this->pinnacleService = $pinnacleService;
        $this->customerProductRepository = $customerProductRepository;
        $this->container = $container;
    }

    // TODO: Make all the integrations to service so that
    // we can inject via constructor injection
    public function getIntegration(string $providerName): AbstractIntegration
    {
        if (strcasecmp($providerName, 'pinbet') == 0) {
            return $this->container->get(PinnacleAdapterIntegration::class);
        }

        // if (strcasecmp($providerName, 'pwm') == 0) {
        //     $className = $this->config[$providerName]['class'];
        //     $instance = $this->container->get($instance);
        //     dump($instance);
        //     return new PiwiMemberWalletAdapterIntegration();
        // }
        
        if (!array_key_exists($providerName, $this->config)) {
            throw new NoSuchIntegrationException();
        }
        
        $providerConfig = $this->config[$providerName];
        $className = ucfirst($providerName) . 'Integration';
        $class = new \ReflectionClass(__NAMESPACE__ . '\\' .$className);

        return $class->newInstanceArgs([$providerConfig['url']]);
    }
}
