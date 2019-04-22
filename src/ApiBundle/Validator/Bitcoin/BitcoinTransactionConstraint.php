<?php

namespace ApiBundle\Validator\Bitcoin;

use Symfony\Component\Validator\Constraint;

class BitcoinTransactionConstraint extends Constraint
{
    protected $message = "You can only have one pending bitcoin deposit transaction at a time.";
    protected $minMaxDepositMessage = "You can deposit a total of {{ min }} to {{ max }} bitcoin only";
    protected $type = '';

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }
}
