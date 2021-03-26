<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Doctrine\Common\Collections\ArrayCollection;

class UniqueValidator extends ConstraintValidator
{
    private $doctrine;
    private $arrayValues = [];

    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function validate($value, Constraint $constraint)
    {
        $expLang = new ExpressionLanguage();

        if ($value instanceof ArrayCollection) {
            foreach($value as $key => $values) {
                foreach ($values as $id => $val) {
                        $this->arrayValues[$id][] = $val;
                }
            }
        }

        $select = $this->context->getPropertyName();
        if ($constraint->getSelect() !== null) {
            $select = $constraint->getSelect();
        }

        $dql = "SELECT " . $select . " FROM " . $constraint->getEntityClass() . "  WHERE ";

        if ($constraint->getExpression() !== null) {
            $dql .= ' ' . $constraint->getExpression();
        }

        $query = $this->getEntityManager($constraint->getEntityManagerName())->createQuery($dql);

        $param = $requiredKey = '';
        $isPlenty = false;
        if (!empty($constraint->getRequiredKey())) {
            $requiredKey = $constraint->getRequiredKey();
            if ($requiredKey >= 1) {
                $isPlenty = true;
                foreach ($requiredKey as $k => $y) {
                    $param .= $y . " and ";
                    $query->setParameter('value' . $k ,($value instanceof ArrayCollection) ? $this->arrayValues[strtolower($y)] : $value);
                }
            }
            $param = rtrim($param, ' and ');
        }

        if (!empty($constraint->getExpressionParams())) {
            foreach ($constraint->getExpressionParams() as $key => $expression) {
                $query->setParameter($key, $expLang->evaluate($expression, ['object' => $value]));
            }
        }
        
        if (!empty($constraint->getExpressionParams2())) {
            foreach ($constraint->getExpressionParams2() as $key => $param) {
                $query->setParameter($key, $expLang->evaluate($param, [
                    'value' => $value,
                    'object' => $this->context->getObject(),
                    'root' => $this->context->getRoot()->getData(),
                ]));
            }
        }

        $values = '';
        $query = $query->setMaxResults(1);
        $result = $query->getOneOrNullResult();

        if (!is_null($result)) {
            if (!empty($requiredKey)) {
                foreach ($requiredKey as $k => $y) {
                    $values .= $result[$y] . " and ";
                }
                $values = rtrim($values, " and ");
                $this->context->buildViolation($constraint->getMessage())
                    ->setParameter('{{ string }}',  $param . " " . $values . "")
                    ->atPath("[0]")
                    ->addViolation();
            } else {
                $this->context->buildViolation($constraint->getMessage())
                    ->atPath($constraint->getErrorPath())
                    ->addViolation();
            }
        }
    }

    private function getEntityManager($name): \Doctrine\ORM\EntityManager
    {
        return $this->doctrine->getEntityManager($name);
    }
}
