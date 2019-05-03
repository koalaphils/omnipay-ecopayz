<?php

declare(strict_types = 1);

namespace PinnacleBundle\Validator;

use Symfony\Component\Validator\Constraint;

class PinnacleHasEnoughBalanceConstraint extends Constraint
{
    protected $message = "Balance not enough";
    protected $userCode;
    protected $isUserCodeExpression = false;

    public function getTargets()
    {
        return self::PROPERTY_CONSTRAINT;
    }

    public function getRequiredOptions()
    {
        return ['userCode'];
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this->message;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setUserCode(string $userCode): self
    {
        $this->userCode = $userCode;

        return $this;
    }

    public function getUserCode(): string
    {
        return $this->userCode;
    }

    public function getIsUserCodeExpression(): bool
    {
        return (bool) $this->isUserCodeExpression;
    }

    public function setIsUserCodeExpression(bool $isUserCodeExpression): self
    {
        $this->isUserCodeExpression = $isUserCodeExpression;

        return $this;
    }
}