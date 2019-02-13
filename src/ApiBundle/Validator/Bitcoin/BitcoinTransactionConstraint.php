<?php

namespace ApiBundle\Validator\Bitcoin;

use Symfony\Component\Validator\Constraint;

class BitcoinTransactionConstraint extends Constraint
{
    private $message = "You can only have one pending bitcoin deposit transaction at a time.";
    private $minMaxDepositMessage = "You can deposit a total of {{ min }} to {{ max }} bitcoin only";

    public function setMessage(string $message): self
    {
        $this->message = $message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getMinMaxDepositMessage(): string
    {
        return $this->minMaxDepositMessage;
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
