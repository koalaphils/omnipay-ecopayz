<?php

namespace ApiBundle\Form\DataTransformer;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use DbBundle\Entity\CustomerPaymentOption;

class PaymentOptionTransformer implements DataTransformerInterface
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
        
        if ($value instanceof CustomerPaymentOption) {
            return $value;
        }

        $customerPaymentOption = $this->getRepository()->find($value);
        if ($customerPaymentOption !== null) {
            return $customerPaymentOption;
        }

        throw new TransformationFailedException(sprintf(
            'An customer payment option with id "%s" does not exists.',
            $value
        ));
    }

    public function transform($value)
    {
        if ($value instanceof CustomerPaymentOption) {
            return $value->getId();
        }

        return $value;
    }


    private function getRepository(): \DbBundle\Repository\CustomerPaymentOptionRepository
    {
        return $this->doctrine->getRepository('DbBundle:CustomerPaymentOption');
    }
}
