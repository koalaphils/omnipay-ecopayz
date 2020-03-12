<?php

namespace ProductIntegrationBundle;

use ProductIntegrationBundle\Exception\NoSuchIntegrationException;
use ProductIntegrationBundle\Integration\ProductIntegrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProductIntegrationFactory
{
    private $resolvedIntegrations;
    private $container;

    public function __construct(ContainerInterface $container, array $resolvedIntegrations)
    {
        $this->container = $container;
        $this->resolvedIntegrations = $resolvedIntegrations;
    }

    public function getIntegration(string $integrationName): ProductIntegrationInterface
    {
        if (!isset($this->resolvedIntegrations[strtolower($integrationName)])) {
            throw new NoSuchIntegrationException();
        }

        return $this->container->get($this->resolvedIntegrations[strtolower($integrationName)]);
    }
}