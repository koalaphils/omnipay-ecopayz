<?php

/**
 * This is thrown when Guzzle throws ConnectException
 * in which case either the integration is down
 * or we have problem in connecting to the particular integration
 */
namespace ApiBundle\ProductIntegration;

class IntegrationNotAvailableException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Integration is not currently available.');
    }
}