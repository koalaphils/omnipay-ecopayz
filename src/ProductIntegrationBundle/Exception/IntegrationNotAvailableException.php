<?php

/**
 * This is thrown when Guzzle throws ConnectException
 * in which case either the integration is down
 * or we have problem in connecting to the particular integration
 */
namespace ProductIntegrationBundle\Exception;

class IntegrationNotAvailableException extends \Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}