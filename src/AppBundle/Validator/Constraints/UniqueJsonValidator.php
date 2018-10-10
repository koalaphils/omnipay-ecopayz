<?php
namespace AppBundle\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Description of UniqueJsonValidator
 * this would search a particular json value that excludes entity itself in terms of updating function
 *
 */
class UniqueJsonValidator extends ConstraintValidator
{
    private $doctrine;

    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!($constraint instanceof Unique)) {
            return;
        }
        
        $expLang = new ExpressionLanguage();

        $exprValues = [
            'object' => $value,
            'root' => $this->context->getRoot(),
        ];
        $identifier = $this->getIdentifier($constraint->getEntityClass(false), $constraint);
        $qb = $this->getManager($constraint->getEntityManagerName())->createQueryBuilder();
        $qb->select('COUNT(e.' . $identifier . ') AS resultTotal');
        $qb->from($constraint->getEntityClass(false), 'e');
        foreach ($constraint->getJoins() as $alias => $field) {
            $qb->join($field, $alias);
        }

        if ($constraint->getExpression() !== null) {
            $expression = $constraint->getExpression();
            if ($value->getId()) {
                $expression .= ' and e.id != :id';
            }

            $qb->andWhere($expression);
            foreach ($constraint->getExpressionParams() as $key => $param) {
                $qb->setParameter($key, $expLang->evaluate($param, $exprValues));
            }

            if ($value->getId()) {
                $qb->setParameter(':id', $value->getId());
            }
        }
        
        $count = $qb->getQuery()->getSingleScalarResult();
        if ($count > 0) {
            $parameters = [];
            foreach ($constraint->getViolationValues() as $key => $value) {
                $realValue = $expLang->evaluate($value, $exprValues);
                $parameters[$key] = $realValue;
            }
            $this->context->buildViolation($constraint->getMessage())
                ->atPath($constraint->getErrorPath())
                ->setParameters($parameters)
                ->addViolation();
        }
    }

    protected function getIdentifier($class, Constraint $constraint)
    {
        $metaData = $this->getManager($constraint->getEntityManagerName())->getClassMetadata($class);

        return $metaData->getIdentifierFieldNames()[0];
    }

    protected function getManager($name = 'default'): EntityManagerInterface
    {
        return $this->doctrine->getManager($name);
    }
}