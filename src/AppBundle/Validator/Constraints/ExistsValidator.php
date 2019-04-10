<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Form\Form;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExistsValidator extends ConstraintValidator
{
    private $doctrine;

    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function validate($value, Constraint $constraint)
    {
        $expLang = new ExpressionLanguage();
        $rootData = $this->context->getRoot();
        if ($rootData instanceof Form) {
            $rootData = $rootData->getData();
        }
        if (!($constraint instanceof Exists)) {
            throw new UnexpectedTypeException($constraint, sprintf('%s\Exists', __NAMESPACE__));
        }
        
        if ($this->ignoreValidation($value, $constraint)) {
            return;
        }
        
        if ($constraint->getValuePath() !== null) {
            $value = $expLang->evaluate($constraint->getValuePath(), [
                'value' => $value,
                'object' => $this->context->getObject(),
                'root' => $rootData,
            ]);
        }
        
        $column = $this->context->getPropertyName();
        if ($constraint->getColumn() !== null) {
            $column = $constraint->getColumn();
        }
        
        $dql = "SELECT COUNT(e) totalEntity FROM " . $constraint->getEntityClass() . " AS e " . $constraint->getJoinExpression() . " WHERE e." . $column . " = :value";
        if ($constraint->getExpression() !== null) {
            $dql .= ' ' . $constraint->getExpression();
        }
        
        $query = $this->getEntityManager($constraint->getEntityManagerName())->createQuery($dql)
            ->setParameter('value', $value);
        if ($constraint->getExpression() !== null) {
            foreach ($constraint->getExpressionParams() as $key => $param) {
                $query->setParameter($key, $expLang->evaluate($param, [
                    'value' => $value,
                    'object' => $this->context->getObject(),
                    'root' => $rootData,
                ]));
            }
        }
        
        $result = (int) $query->getSingleScalarResult();
        
        if ($result === 0) {
            $this->context->buildViolation($constraint->getMessage())->addViolation();
        }
    }
    
    private function ignoreValidation($value, Exists $constraint)
    {
        if ($constraint->ignoreNull() && $value === null) {
            return true;
        }
        
        return false;
    }

    private function getEntityManager($name): \Doctrine\ORM\EntityManager
    {
        return $this->doctrine->getEntityManager($name);
    }
}
