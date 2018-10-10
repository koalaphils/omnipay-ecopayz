<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DecimalLength extends Constraint
{
    protected $message = 'This value should contain {{ length }} decimal places only.';
    protected $length = 2;

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLength(): int
    {
        return $this->length;
    }
}