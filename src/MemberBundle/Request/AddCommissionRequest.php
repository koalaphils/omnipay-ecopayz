<?php

namespace MemberBundle\Request;

use DbBundle\Entity\Customer;
use DbBundle\Entity\MemberCommission;

/**
 * Description of AddCommissionRequest
 *
 * @author cydrick
 */
class AddCommissionRequest
{
    private $product;
    private $commission;
    private $status;
    private $memberCommission;

    public static function fromEntity(Customer $customer): AddCommissionRequest
    {
        $request = new AddCommissionRequest();
        $request->memberCommission = new MemberCommission();
        $request->memberCommission->setMember($customer);
        $request->commission = 0;
        $request->status = true;

        return $request;
    }

    public function getProduct(): ?int
    {
        return $this->product;
    }

    public function setProduct(int $product): void
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

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }

    public function getMemberCommission(): MemberCommission
    {
        return $this->memberCommission;
    }
}
