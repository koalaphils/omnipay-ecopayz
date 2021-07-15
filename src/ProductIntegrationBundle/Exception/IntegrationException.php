<?php

/**
 * Thrown when an integration throws 400 error codes
 */

namespace ProductIntegrationBundle\Exception;

use Exception;

class IntegrationException extends Exception
{
    public function __construct(string $body, string $code, Throwable $previous = null)
    {
        parent::__construct($body, $code, $previous);
    }
}