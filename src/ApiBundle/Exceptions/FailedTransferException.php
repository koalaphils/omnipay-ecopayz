<?php

namespace ApiBundle\Exceptions;

use Exception;

class FailedTransferException extends Exception
{
    public function __construct(string $body, string $code)
    {
        parent::__construct($body, $code);
    }
}