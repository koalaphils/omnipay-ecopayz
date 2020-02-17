<?php

/**
 * Thrown when an integration throws 400 error codes
 */
namespace ApiBundle\ProductIntegration;

class IntegrationException extends \Exception
{
    public function __construct(string $body, string $code)
    {
        parent::__construct($response->getBody(), $response->getStatusCode());
    }
}