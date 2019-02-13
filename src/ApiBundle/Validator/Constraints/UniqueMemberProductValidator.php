<?php

namespace ApiBundle\Validator\Constraints;

use DbBundle\Entity\User;
use DbBundle\Repository\CustomerProductRepository as MemberProductRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UniqueMemberProductValidator extends ConstraintValidator
{
    private $memberProductRepository;
    private $tokenStorage;

    public function __construct(MemberProductRepository $memberProductRepository, TokenStorageInterface $tokenStorage)
    {
        $this->memberProductRepository = $memberProductRepository;
        $this->tokenStorage = $tokenStorage;
    }

    public function validate($memberProductList, Constraint $constraint)
    {
        $productNames = [];
        $member = $this->getUser()->getMember();
        $productCodes = array_column(
            $this->memberProductRepository->getProductCodeListOfMember($member->getId()),
            null,
            'code'
        );

        foreach ($memberProductList->getMemberProducts() as $memberProduct) {
            $productCode = $memberProduct->getProduct();

            if (array_key_exists($productCode, $productCodes)) {
                $productNames[] = $productCodes[$productCode]['name'];
            }
        }

        if (!empty($productNames)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ products }}', implode(', ', $productNames))
                ->addViolation();
        }
    }

    private function getUser(): User
    {
        return $this->tokenStorage->getToken()->getUser();
    }
}