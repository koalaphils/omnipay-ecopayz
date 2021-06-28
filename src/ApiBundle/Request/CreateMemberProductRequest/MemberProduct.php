<?php

namespace ApiBundle\Request\CreateMemberProductRequest;

use DbBundle\Entity\Product;

class MemberProduct
{
    private $product;
    private $username;
    private $isAgree;

    public function __construct()
    {
        $this->username = '';
        $this->product = '';
        $this->isAgree = false;
    }

    public static function create(): self
    {
        return new self();
    }

    public function getProduct(): string
    {
        return $this->product;
    }

    public function setProduct(string $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function setIsAgree(bool $isAgree): self
    {
        $this->isAgree = $isAgree;

        return $this;
    }

    public function getIsAgree(): bool
    {
        return $this->isAgree;
    }
}