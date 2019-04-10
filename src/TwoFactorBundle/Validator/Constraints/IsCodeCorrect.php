<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class IsCodeCorrect extends Constraint
{
    /**
     * @var string
     */
    protected $message = "Invalid Code";

    /**
     * @var string
     */
    protected $payloadPath = "";

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getPayloadPath(): string
    {
        return $this->payloadPath;
    }
}