<?php

namespace ApiBundle\ProductIntegration;

class ProductIntegrationFactory
{
    private $config;

    public function __construct(array $config) 
    {
        $this->config = $config;
    }

    // OptionsResolver?
    public function getIntegration(string $providerName): AbstractIntegration
    {
        if (!array_key_exists($providerName, $this->config)) {
            throw new NoSuchIntegrationException();
        }
        $providerConfig = $this->config[$providerName];
        $className = ucfirst($providerName) . 'Integration';
        $class = new \ReflectionClass(__NAMESPACE__ . '\\' .$className);

        return $class->newInstanceArgs([$providerConfig['url']]);
    }
}
