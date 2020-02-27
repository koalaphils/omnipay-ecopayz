<?php

/**
 * This is thrown when Guzzle throws ConnectException
 * in which case either the integration is down
 * or we have problem in connecting to the particular integration
 */
namespace ApiBundle\ProductIntegration;

class IntegrationNotAvailableException extends \Exception
{
    public function __construct(string $url)
    {
        parent::__construct('Integration ' . $url .  ' is not currently available.');
    }
}