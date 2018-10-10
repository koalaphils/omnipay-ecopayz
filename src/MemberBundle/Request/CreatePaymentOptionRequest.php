<?php

namespace MemberBundle\Request;

use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\CustomerPaymentOption as MemberPaymentOption;

/**
 * Description of CreatePaymentOptionRequest
 *
 * @author cydrick
 */
class CreatePaymentOptionRequest
{
    private $memberPaymentOption;
    private $type;
    private $isActive;
    private $fields;
    private $member;

    public static function fromEntity(Member $member): CreatePaymentOptionRequest
    {
        $request = new CreatePaymentOptionRequest();
        $request->memberPaymentOption = new MemberPaymentOption();
        $request->memberPaymentOption->setMember($member);
        $request->member = $member;

        return $request;
    }

    private function __construct()
    {
        $this->type = '';
        $this->isActive = true;
        $this->fields = [];
    }

    public function getMemberPaymentOption(): MemberPaymentOption
    {
        return $this->memberPaymentOption;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }
}
