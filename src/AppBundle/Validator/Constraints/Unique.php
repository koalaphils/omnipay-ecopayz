<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class Unique extends Constraint
{
    protected $message = "unique.message";
    protected $entityClass;
    protected $entityClass2;
    protected $joins = [];
    protected $em = 'default';
    protected $expression = null;
    protected $valuePath = null;
    protected $expressionParams = [];
    protected $expressionParams2 = [];
    protected $isJoinTable = false;
    protected $select;
    protected $requiredKey = [];
    protected $errorPath = null;
    protected $violationValues = [];
    protected $validator = 'custom_unique';

    public function getMessage($newMessage = null)
    {
        if (!is_null($newMessage)) {
            $this->message = $newMessage;
        }
        return $this->message;
    }

    public function validatedBy()
    {
        return $this->validator;
    }

    public function getEntityManagerName(): string
    {
        return $this->em;
    }

    public function getJoins(): array
    {
        return $this->joins;
    }

    public function getIsJoinTable(): bool
    {
        return $this->isJoinTable;
    }

    public function getEntityClass($addAlias = true): string
    {
        if (!$addAlias) {
            return $this->entityClass;
        }

        $table = "";
        if (!$this->getIsJoinTable()) {
            $table = $this->entityClass . " AS e";
        } else {
            $table = $this->entityClass . " AS e INNER JOIN " . $this->entityClass2 . " AS e2 ";
        }
        return $table;
    }

    public function getRequiredOptions()
    {
        return [
            'entityClass',
        ];
    }

    public function getTargets()
    {
        return [self::PROPERTY_CONSTRAINT, self::CLASS_CONSTRAINT];
    }

    public function getExpression(): ?string
    {
        return $this->expression;
    }


    public function getExpressionParams()
    {
        return $this->expressionParams;
    }

    public function getExpressionParams2()
    {
        return $this->expressionParams2;
    }

    public function getValuePath(): ?string
    {
        return $this->valuePath;
    }

    public function getRequiredIndeces(): array
    {
        return $this->requiredIndeces;
    }

    public function getSelect(): ?string
    {
        return $this->select;
    }

    public function getRequiredKey(): ?array
    {
        return $this->requiredKey;
    }

    public function getViolationValues(): array
    {
        return $this->violationValues;
    }

    public function getErrorPath()
    {
        return $this->errorPath;
    }
}
