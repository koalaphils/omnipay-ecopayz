<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Exists extends Constraint
{
    protected $message = "Not exists";
    protected $entityClass;
    protected $column;
    protected $em = 'default';
    protected $expression = null;
    protected $valuePath = null;
    protected $expressionParams = [];
    protected $ignoreNull = false;

    public function getMessage()
    {
        return $this->message;
    }

    public function getRequiredOptions()
    {
        return [
            'entityClass',
        ];
    }

    public function validatedBy()
    {
        return "exists";
    }

    public function getEntityManagerName(): string
    {
        return $this->em;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getExpression(): ?string
    {
        return $this->expression;
    }

    public function getColumn(): ?string
    {
        return $this->column;
    }
    
    public function getValuePath(): ?string
    {
        return $this->valuePath;
    }
    
    public function getExpressionParams(): array
    {
        return $this->expressionParams;
    }
    
    public function ignoreNull(): bool
    {
        return $this->ignoreNull;
    }
}
