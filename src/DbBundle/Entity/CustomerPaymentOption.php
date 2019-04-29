<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Customer as Member;

/**
 * CustomerPaymentOption
 */
class CustomerPaymentOption extends Entity implements ActionInterface, TimestampInterface, AuditInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    private $type;
    private $isActive;
    private $fields;
    private $customer;
    private $paymentOption;

    public function __construct()
    {
        $this->isActive = true;
        $this->setFields([]);
    }

    public function addField($field, $value)
    {
        $this->setFields(array_append($this->getFields(), $value, $field));

        return $this;
    }

    public function setField($field, $value)
    {
        array_set($this->fields, $field, $value);

        return $this;
    }

    public function setAccountId(string $accountId): self
    {
        return $this->setField('account_id', $accountId);
    }
    
    public function setBitcoinAddress(string $bitcoinAddress): self
    {
        return $this->setField('account_id', $bitcoinAddress);
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getMember(): Member
    {
        return $this->customer;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function getPaymentOption(): PaymentOption
    {
        return $this->paymentOption;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setCustomer($customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function setMember(Member $member): self
    {
        return $this->setCustomer($member);
    }

    public function setFields($fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function getField($field, $default = null)
    {
        return array_get($this->fields, $field, $default);
    }

    public function getAccountIdField(): ?string
    {
        return $this->getField('account_id') ?? '';
    }

    public function getBitcoinField(): string
    {
        return $this->getField('account_id') ?? '';
    }

    public function setIsActive($isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setPaymentOption($paymentOption): self
    {
        $this->paymentOption = $paymentOption;

        return $this;
    }

    public function getEmail()
    {
        $fields = $this->getFields();
        foreach ($fields as $fieldname => $fieldValue) {
            if ($fieldname === 'email') {
                return $fieldValue;
            }
        }

        return '';
    }

    public function clearCustomFields(): self
    {
        $this->fields = [];

        return $this;
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_CUSTOMER_PAYMENT_OPTION;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt'];
    }

    public function getAssociationFields()
    {
        return ['customer', 'paymentOption'];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getLabel()
    {
        return sprintf('%s (%s)', $this->getCustomer()->getFullName(), $this->getPaymentOption()->getName());
    }

    public function isAudit()
    {
        return true;
    }

    public function suspend()
    {
        $this->setIsActive(false);
    }

    public function enable()
    {
        $this->setIsActive(true);
    }

    public function getAuditDetails(): array
    {
        return ['type' => $this->getType(), 'customer' => $this->getCustomer(), 'paymentOption' => $this->getPaymentOption(), 'fields' => $this->getFields()];
    }

    public function setForWithdrawal(): self
    {
        $this->setField('is_withdrawal', 1);

        return $this;
    }

    public function setForDeposit(): self
    {
        $this->setField('is_deposit', 1);

        return $this;
    }
}
