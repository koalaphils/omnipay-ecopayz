<?php

namespace MemberBundle\Constraints;

use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Repository\CustomerProductRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueCustomerProductConstraintValidator extends ConstraintValidator
{
    private $em;

    public function __construct(EntityManager $entityManager)
    {
        $this->em = $entityManager;
    }

    public function validate($value, Constraint $constraint)
    {
        if ($constraint->getAction() == "create") {
            $customerId = $value->getCustomerProduct()->getCustomerID();
            $member = $this->getCustomerRepository()->find($customerId);
            $currencyId = $member->getCurrency()->getId();
        } else {
            $customerProductId = $value->getCustomerProduct();
            $memberProduct = $this->getCustomerProductRepository()->find($customerProductId);
            $currencyId = $memberProduct->getCustomer()->getCurrency()->getId();
        }

        if($value->getProduct() !== null) {
            if ($this->hasUsernameProductAndCurrency($value->getUsername(), $value->getProduct(), $currencyId)) {
                if ($constraint->getAction() == "update") {
                    if ($this->hasNoChangeInProductUsername($memberProduct, $value)) {
                        $result = null;
                    } else {
                        if ($this->hasSameProductUsernameWithOthers($value)) {
                            $result = $constraint->getWithError();
                        } else {
                            $result = $this->getCustomerProductRepository()->findByUsernameProductAndCurrency($value->getUsername(), $value->getProduct(), $currencyId);
                        }
                    }
                } else {
                    if ($this->hasSameProductUsernameWithOthers($value)) {
                        $result = $constraint->getWithError();
                    } else {
                        $result = $this->getCustomerProductRepository()->findByUsernameProductAndCurrency($value->getUsername(), $value->getProduct(), $currencyId);
                    }
                }

                if (!is_null($result)) {
                    $this->context->buildViolation($constraint->getMessage())
                        ->setParameter('{{ string }}', $constraint->getMessage())
                        ->atPath($constraint->getErrorPath())
                        ->addViolation();
                }
            }
        }
    }

    private function hasSameProductUsernameWithOthers($value): bool
    {
        $result = $this->getCustomerProductRepository()->findByUsernameProduct($value->getUsername(), $value->getProduct());

        return !is_null($result) ? true : false;
    }

    private function hasNoChangeInProductUsername(CustomerProduct $memberProduct, $value): bool
    {
        return $memberProduct->getUserName() === $value->getUsername() && $memberProduct->getProductID() === $value->getProduct();
    }

    private function hasUsernameProductAndCurrency(string $username, int $product, int $currency): bool
    {
        return !empty($username) && !empty($product) && !empty($currency);
    }

    private function getCustomerProductRepository()
    {
        return $this->em->getRepository(CustomerProduct::class);
    }

    private function getCustomerRepository()
    {
        return $this->em->getRepository(Customer::class);
    }
}
