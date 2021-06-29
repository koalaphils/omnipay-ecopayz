<?php

namespace ApiBundle\Model\RegisterV2;

class Bookie
{
    private $product;
    private $username;

    public function getProduct():? \DbBundle\Entity\Product
    {
        return $this->product;
    }

    public function setProduct($product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getUsername():? string
    {
        return $this->username;
    }

    public function setUsername($username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getFormattedUsername(): string
    {
        $username = $this->getUsername();

        return !is_null($username) ? $username : uniqid('tmp_' . str_replace(' ', '', $this->getProduct()->getName()) . '_');
    }
}