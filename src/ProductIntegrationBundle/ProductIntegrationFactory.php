<?php

namespace ProductIntegrationBundle;

class ProductIntegrationFactory
{
    private $resolvedIntegrations;

    public function __construct(array $resolvedIntegrations)
    {
        $this->resolvedIntegrations = $resolvedIntegrations;
    }

    public function getIntegration(string $integrationName)
    {
        dump('HAHAHA');
    }
}