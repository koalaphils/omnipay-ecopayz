<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;

/**
 * CustomerGroupGateway
 */
class CustomerGroupGateway implements ActionInterface, TimestampInterface, AuditInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    private $gateway;
    private $customerGroup;
    private $conditions;

    public function getId()
    {
        return ['gateway' => $this->getGateway()->getId(), 'customerGroup' => $this->getCustomerGroup()->getId()];
    }

    public function getConditions()
    {
        return $this->conditions;
    }

    public function getCustomerGroup(): CustomerGroup
    {
        return $this->customerGroup;
    }

    public function getGateway(): ?Gateway
    {
        return $this->gateway;
    }

    public function setConditions($conditions)
    {
        $this->conditions = $conditions;

        return $this;
    }

    public function setCustomerGroup(CustomerGroup $customerGroup)
    {
        $this->customerGroup = $customerGroup;

        return $this;
    }

    public function setGateway(Gateway $gateway)
    {
        $this->gateway = $gateway;

        return $this;
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_CUSTOMER_GROUP;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt'];
    }

    public function getAssociationFields()
    {
        return ['gateway', 'customerGroup'];
    }

    public function getIdentifier()
    {
        return null;
    }

    public function getLabel()
    {
        return sprintf('%s (%s)', $this->getCustomerGroup()->getName(), $this->getGateway()->getName());
    }

    public function isAudit()
    {
        return true;
    }

    public function getAuditDetails(): array
    {
        return ['gateway' => $this->getGateway(), 'customerGroup' => $this->getCustomerGroup()];
    }
}
