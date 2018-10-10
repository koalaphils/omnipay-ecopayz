<?php

namespace MemberBundle\Request;
use DbBundle\Entity\Customer;
use DbBundle\Entity\MemberCommission;

/**
 * Description of AddCommissionRequest
 *
 * @author cydrick
 */
class UpdateCommissionRequest
{
    private $product;
    private $commission;
    private $status;
    private $resourceId;
    private $memberId;
    private $productId;

    public function __construct()
    {
        $this->status = false;
        $this->resourceId = '';
        $this->commission = 0;
    }

    public static function fromEntity(Customer $customer): UpdateCommissionRequest
    {
        $request = new UpdateCommissionRequest();
        $request->memberCommission = new MemberCommission();
        $request->memberCommission->setMember($customer);
        $request->commission = 0;
        $request->status = true;

        return $request;
    }

    public function getMember() : Customer
    {
        return $this->memberCommission->getMember();
    }

    public function getProduct(): ?int
    {
        return $this->product;
    }

    public function setProduct(string $product): void
    {
        $this->product = $product;
    }

    public function getCommission(): string
    {
        return (string) $this->commission;
    }

    public function setCommission(string $commission): void
    {
        $this->commission = $commission;
    }

// NOTE: see https://ac88dev.atlassian.net/browse/AC66-1017
//    public function getStatus(): bool
//    {
//        return $this->status;
//    }

//    public function setStatus(bool $status): void
//    {
//        $this->status = $status;
//    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId = null): void
    {
        if ($resourceId === null ) {
            $resourceId = '';
        }
        $this->resourceId = $resourceId;
    }

    public function setMemberId(int $memberId)
    {
        $this->memberId = $memberId;
    }

    public function getMemberId() :? int
    {
        return $this->memberId;
    }
    
    public function setProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductId() :? int
    {
        return $this->productId;
    }
}
