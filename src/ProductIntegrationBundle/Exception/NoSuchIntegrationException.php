<?php

/**
 * Thrown when trying to get non-existing integration.
 */
namespace ProductIntegrationBundle\Exception;

class NoSuchIntegrationException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Integration doesnt exist.');
    }
}