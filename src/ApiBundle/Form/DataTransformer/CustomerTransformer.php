<?php

namespace ApiBundle\Form\DataTransformer;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use DbBundle\Entity\Customer;

class CustomerTransformer implements DataTransformerInterface
{
    private $doctrine;

    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function reverseTransform($value)
    {
        if ($value instanceof Customer) {
            return $value;
        }

        $customer = $this->getRepository()->find($value);
        if ($customer !== null) {
            return $customer;
        }

        throw new TransformationFailedException(sprintf(
            'An customer with id "%s" does not exists.',
            $value
        ));
    }

    public function transform($value)
    {
        if ($value instanceof Customer) {
            return $value->getId();
        }

        return $value;
    }


    private function getRepository(): \DbBundle\Repository\CustomerRepository
    {
        return $this->doctrine->getRepository('DbBundle:Customer');
    }
}
