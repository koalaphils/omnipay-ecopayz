<?php

declare(strict_types = 1);

namespace PinnacleBundle\Component\Exceptions;

use Throwable;

class PinnacleError extends \Exception
{
    /**
     * @var string
     */
    private $data;

    public function __construct(array $data, string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }

    public function getData(): string
    {
        return $this->data;
    }
}