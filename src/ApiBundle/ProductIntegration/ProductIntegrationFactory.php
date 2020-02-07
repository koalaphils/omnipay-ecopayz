<?php

namespace ApiBundle\ProductIntegration;

class ProductIntegrationFactory
{
    private $config;

    public function __construct(array $config) 
    {
        $this->config = $config;
    }

    // TODO: Throw exception if provider doesn't exist
    // OptionsResolver?
    public function getIntegration(string $providerName): AbstractIntegration
    {
        $providerConfig = $this->config[$providerName];
        $className = ucfirst($providerName) . 'Integration';
        $class = new \ReflectionClass(__NAMESPACE__ . '\\' .$className);

        return $class->newInstanceArgs([$providerConfig['url']]);
    }
}
