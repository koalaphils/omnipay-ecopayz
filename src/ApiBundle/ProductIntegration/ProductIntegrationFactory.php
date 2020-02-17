<?php

namespace ApiBundle\ProductIntegration;

use PinnacleBundle\Service\PinnacleService;

class ProductIntegrationFactory
{
    private $config;
    private $pinnacleService;

    public function __construct(array $config, PinnacleService $pinnacleService) 
    {
        $this->config = $config;
        $this->pinnacleService = $pinnacleService;
    }

    public function getIntegration(string $providerName): AbstractIntegration
    {
        // Return an adapter class
        if ($providerName === 'pinbet') {
            return new PinnacleAdapterIntegration($this->pinnacleService);
        }

        if (!array_key_exists($providerName, $this->config)) {
            throw new NoSuchIntegrationException();
        }
        
        $providerConfig = $this->config[$providerName];
        $className = ucfirst($providerName) . 'Integration';
        $class = new \ReflectionClass(__NAMESPACE__ . '\\' .$className);

        return $class->newInstanceArgs([$providerConfig['url']]);
    }
}
