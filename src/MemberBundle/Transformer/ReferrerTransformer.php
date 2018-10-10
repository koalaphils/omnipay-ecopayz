<?php

namespace MemberBundle\Transformer;

/**
 * Description of ReferrerTransformer
 *
 * @author cydrick
 */
class ReferrerTransformer implements \Symfony\Component\Form\DataTransformerInterface
{
    private $doctrine;

    public function __construct(\Doctrine\Bundle\DoctrineBundle\Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function reverseTransform($value)
    {
        return $value;
    }

    public function transform($value)
    {
        if ($value) {
            $referrer = $this->getCustomerRepository()->find($value);

            return [$referrer->getFullName() . '(' . $referrer->getUser()->getUsername() . ')' => $referrer->getId()];
        }
        return $value;
    }

    private function getCustomerRepository(): \DbBundle\Repository\CustomerRepository
    {
        return $this->doctrine->getRepository(\DbBundle\Entity\Customer::class);
    }
}
