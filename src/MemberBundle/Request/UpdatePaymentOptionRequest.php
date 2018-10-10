<?php

namespace MemberBundle\Request;

use DbBundle\Entity\CustomerPaymentOption as MemberPaymentOption;

/**
 * Description of UpdatePaymentOptionRequest
 *
 * @author cydrick
 */
class UpdatePaymentOptionRequest
{
    private $memberPaymentOption;
    private $type;
    private $isActive;
    private $fields;
    private $member;

    public static function fromEntity(MemberPaymentOption $memberPaymentOption): UpdatePaymentOptionRequest
    {
        $request = new UpdatePaymentOptionRequest();
        $request->memberPaymentOption = $memberPaymentOption;
        $request->member = $memberPaymentOption->getMember();

        $request->setFields($memberPaymentOption->getFields());
        $request->setType($memberPaymentOption->getPaymentOption()->getCode());
        $request->setIsActive($memberPaymentOption->getIsActive());

        return $request;
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
