<?php

namespace ApiBundle\Form\DataTransformer;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use DbBundle\Entity\CustomerProduct;

class CustomerProductTransformer implements DataTransformerInterface
{
    private $doctrine;

    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function reverseTransform($value)
    {
        if ($value === null) {
            return null;
        }
        
        if ($value instanceof CustomerProduct) {
            return $value;
        }

        if (is_array($value)) {
            $qb = $this->getRepository()->createQueryBuilder('cp');
            $qb->join('cp.product', 'p');
            $qb->join('cp.customer', 'c');
            $qb->select('cp, p, c');
            $qb->where('cp.userName = :username AND p.code = :code');
            $qb->setParameters($value);
            $customerProduct = $qb->getQuery()->getOneOrNullResult();
        } else {
            $customerProduct = $this->getRepository()->find($value);
        }
        
        if ($customerProduct !== null) {
            return $customerProduct;
        }
        
        if (is_array($value)) {
            $errorMessage = sprintf(
                'A customer product with username "%s" and code "%s" does not exist.',
                $value['username'],
                $value['code']
            );
        } else {
            $errorMessage = sprintf(
                'A customer product with username "%s" and code "%s" does not exist.',
                $value
            );
        }

        throw new TransformationFailedException($errorMessage);
    }

    public function transform($value)
    {
        if ($value instanceof CustomerProduct) {
            return $value->getId();
        }

        return $value;
    }

    private function getRepository(): \DbBundle\Repository\CustomerProductRepository
    {
        return $this->doctrine->getRepository('DbBundle:CustomerProduct');
    }
}
