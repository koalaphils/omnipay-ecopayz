<?php

/**
 * Thrown when an integration throws 400 error codes
 */
namespace ProductIntegrationBundle\Exception;

class IntegrationException extends \Exception
{
    public function __construct(string $body, string $code)
    {
        parent::__construct($body, $code);
    }
}