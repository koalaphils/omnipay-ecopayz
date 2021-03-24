<?php

namespace ProductBundle\Request;

use DbBundle\Entity\Product;
use DbBundle\Entity\ProductCommission;

class ProductFormRequest
{
    private $id;
    private $product;
    private $code;
    private $name;
    private $url;
    private $betadminToSync;
    private $isActive;
    private $logo;
    private $commission;
    private $productCommission;

    public function __construct()
    {
        $this->code = '';
        $this->name = '';
        $this->url = 'http://';
        $this->betadminToSync = false;
        $this->isActive = false;
        $this->logo = '';
        $this->commission = 0;
        $this->productCommission = new ProductCommission();
    }

    public static function formEntity(Product $product): ProductFormRequest
    {
        $request = new ProductFormRequest();
        $request->id = $product->getId();
        $request->setCode($product->getCode());
        $request->setName($product->getName());
        $request->setBetadminToSync($product->getBetadminToSync());
        $request->setIsActive($product->getIsActive());
        if ($product->getUrl() !== null) {
            $request->setUrl($product->getUrl());
        }
        if ($product->getLogo() !== null) {
            $request->setLogo($product->getLogo());
        }
        $request->product = $product;
        $request->setProductCommission(new ProductCommission());

        return $request;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        if ($code === null) {
            $code = '';
        }
        $this->code = $code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        if ($name === null) {
            $name = '';
        }

        $this->name = $name;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        if ($url === null) {
            $url = '';
        }
        $this->url = $url;
    }

    public function getBetadminToSync(): bool
    {
        return $this->betadminToSync;
    }

    public function setBetadminToSync(bool $sync): void
    {
        $this->betadminToSync = $sync;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): void
    {
        $this->logo = $logo;
    }

    public function getCommission(): ?string
    {
        return (string) $this->commission;
    }

    public function setCommission(?string $commission): void
    {
        $this->commission = $commission;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProductCommission(ProductCommission $productCommission): void
    {
        $this->productCommission = $productCommission;
        $this->commission = $productCommission->getCommission();
    }

    public function getProductCommission(): ?ProductCommission
    {
        return $this->productCommission;
    }

    public function isCommissionIsUpdated(): bool
    {
        if ($this->getProductCommission()) {
            return ($this->removeTrailingDecimals($this->commission)) !== ($this->removeTrailingDecimals($this->getProductCommission()->getCommission()));
        }
       
        return false;
    }

    private function removeTrailingDecimals(string $commission): string
    {
        eval('$evaluatedCommission = ' . $commission .';');

        return (string) $evaluatedCommission;
    }
}
