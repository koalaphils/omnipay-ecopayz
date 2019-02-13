<?php

namespace ApiBundle\Request\CreateMemberProductRequest;

use Doctrine\Common\Collections\ArrayCollection;

class MemberProductList
{
    private $memberProducts;

    public function __construct()
    {
        $this->memberProducts = new ArrayCollection();
    }

    public static function create(): self
    {
        return new self();
    }

    public function setMemberProducts(ArrayCollection $memberProducts): self
    {
        $this->memberProducts = $memberProducts;

        return $this;
    }

    public function getMemberProducts(): ArrayCollection
    {
        return $this->memberProducts;
    }
}