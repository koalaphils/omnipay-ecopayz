<?php

namespace MemberBundle\Transformer;

use AppBundle\Service\AffiliateService;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\DataTransformerInterface;

class ReferrerTransformer implements DataTransformerInterface
{
    private $doctrine;
    private $affiliateService;

    public function __construct(Registry $doctrine, AffiliateService $affiliateService)
    {
        $this->doctrine = $doctrine;
        $this->affiliateService = $affiliateService;
    }

    public function reverseTransform($value)
    {     
       return $value;
    }

    public function transform($value)
    {
        if ($value) {
            $affiliate = $this->affiliateService->getAffiliate($value)['data'];
            
            return [$affiliate['name'] . '(' . $affiliate['username'] . ')' => $affiliate['user_id']];
        }

        return $value;
    }

    private function getCustomerRepository(): \DbBundle\Repository\CustomerRepository
    {
        return $this->doctrine->getRepository(\DbBundle\Entity\Customer::class);
    }

    private function getUserRepository(): \DbBundle\Repository\UserRepository
    {
        return $this->doctrine->getRepository(\DbBundle\Entity\User::class);
    }
}
