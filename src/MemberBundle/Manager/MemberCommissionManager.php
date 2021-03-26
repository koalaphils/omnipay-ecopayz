<?php

namespace MemberBundle\Manager;

use Symfony\Component\HttpFoundation\Request;
use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\ProductCommission;
use DbBundle\Entity\Customer;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\MemberCommission;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\Product;
use DbBundle\Repository\CustomerProductRepository as MemberProductRepository;
use DbBundle\Repository\MemberCommissionRepository;
use DbBundle\Repository\ProductRepository;

class MemberCommissionManager extends AbstractManager
{
    public function getCommissionSetting(Customer $member, Product $product) : MemberCommission
    {
        $memberCommissionSettingsRepo = $this->get('doctrine.orm.entity_manager')->getRepository(MemberCommission::class);
        $productDefaultCommissionSettingsRepo = $this->get('doctrine.orm.entity_manager')->getRepository(ProductCommission::class);
        $memberCommissionSetting = $memberCommissionSettingsRepo->findByMemberIdAndProductId($member->getId(), $product->getId());

        $commission = new MemberCommission();
        $commission->setProduct($product);

        if ($memberCommissionSetting instanceof MemberCommission) {
            $commission = $memberCommissionSetting;
        } else {
            $productsDefaultComissionSetting = $productDefaultCommissionSettingsRepo->getProductCommissionOfProduct($product->getId());
            if ($productsDefaultComissionSetting instanceof ProductCommission) {
                $commission->setCommission($productsDefaultComissionSetting->getCommission());
                $commission->setCreatedAt($productsDefaultComissionSetting->getCreatedAt());
            } else {
                $commission->setCommission(0);
                $commission->setCreatedAt(new \DateTime);
                $commission->activate();
            }
        }

        return $commission;
    }

    protected function getRepository(): MemberCommissionRepository
    {
        return $this->getDoctrine()->getRepository(MemberCommission::class);
    }

    protected function getMemberProductRepository(): MemberProductRepository
    {
        return $this->getDoctrine()->getRepository(MemberProduct::class);
    }

    protected function getProductRepository(): ProductRepository
    {
        return $this->getDoctrine()->getRepository(Product::class);
    }
}
