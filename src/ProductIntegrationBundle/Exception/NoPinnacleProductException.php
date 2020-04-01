<?php

namespace ProductIntegrationBundle\Exception;

class NoPinnacleProductException extends \Exception
{
    public function __construct(string $url)
    {
        parent::__construct('Cannot login player. No existing product.');
    }
}