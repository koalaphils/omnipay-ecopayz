<?php

/**
 * Extend for more specific Exceptions
 */
namespace ApiBundle\ProductIntegration;

class IntegrationException extends \Exception
{
    public function __construct($response)
    {
        parent::__construct($response->getBody(), $response->getStatusCode());
    }
}