<?php

namespace MemberBundle\Request;
use DbBundle\Repository\ProductRepository;
use DbBundle\Entity\Customer as Member;

/**
 * Description of UpdateRevenueShareRequest
 *
 * @author bianca
 */
class UpdateRevenueShareRequest
{
    private $customer;
    private $productId;
    private $resourceId;
    private $productName;
    private $revenueShareSettings;

    public function __construct()
    {
        $this->product = '';
        $this->revenueShareSettings = [];
        $this->resourceId = '';
    }

    public static function fromEntity(Member $customer): UpdateRevenueShareRequest
    {
        $request = new UpdateRevenueShareRequest();
        $request->customer = $customer;

        return $request;
    }

    public function getCustomer(): Member
    {
        return $this->customer;
    }

    public function getRevenueShareSettings(): array
    {
        return $this->revenueShareSettings;
    }

    public function setRevenueShareSettings(array $revenueShareSettings): void
    {
        $newRevenueShareSettings = [];
        foreach ($revenueShareSettings as $settings) {
            array_push($newRevenueShareSettings, $settings);
        }

        usort($newRevenueShareSettings, function ($field1, $field2) {
            return $field1['min'] - $field2['min'];
        });

        $this->revenueShareSettings = $newRevenueShareSettings;
    }

    public function setProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductId() :? int
    {
        return $this->productId;
    }

    public function getProductName(): ?int
    {
        return $this->productName;
    }

    public function setProductName(string $productName): void
    {
        $this->productName = $productName;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId = null): void
    {
        if (is_null($resourceId)) {
            $resourceId = '';
        }
        $this->resourceId = $resourceId;
    }
}