<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Gateway;

/**
 * CustomerGroup
 */
class CustomerGroup extends Entity implements ActionInterface, TimestampInterface, AuditInterface, AuditAssociationInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    private $gateways;
    private $name;
    private $isDefault;
    private $customers;

    public function __construct($name, $gateways, bool $isDefault = false)
    {
        $this->name = $name;
        $this->isDefault = $isDefault;
        $this->gateways = $gateways;
        $this->customers = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add gateway
     *
     * @param Gateway $gateway
     *
     * @return \DbBundle\Entity\CustomerGroup
     */
    public function addGateway(Gateway $gateway)
    {
        $this->gateways = array_append($this->gateways, $gateway);

        return $this;
    }

    public function addCustomer(Customer $customer): CustomerGroup
    {
        if ($this->customers->contains($customer)) {
            return $this;
        }

        //$this->customers = array_prepend($this->customers, $customer);
        $this->customers->add($customer);
        $customer->addGroup($this);

        return $this;
    }

    public function removeCustomer(Customer $customer): CustomerGroup
    {
        if (!$this->customers->contains($customer)) {
            return $this;
        }

        $this->customers->removeElement($customer);
        $customer->removeGroup($this);

        return $this;
    }

    public function getCustomers()
    {
        return $this->customers;
    }

    /**
     * Get related payment gateways
     *
     * @return \DbBundle\Entity\Gateway[]
     */
    public function getGateways()
    {
        return $this->gateways;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setCustomers($customers)
    {
        $this->customers = $customers;

        return $this;
    }

    /**
     * Set gateways
     *
     * @param \DbBundle\Entity\Gateway[] $gateways
     *
     * @return \DbBundle\Entity\CustomerGroup
     */
    public function setGateways($gateways)
    {
        $this->gateways = $gateways;

        return $this;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return \DbBundle\Entity\CustomerGroup
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return bool
     */
    public function getIsDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_CUSTOMER_GROUP;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt', 'gateways'];
    }

    public function getAssociationFields()
    {
        return [];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getLabel()
    {
        return $this->getName();
    }

    public function isAudit()
    {
        return true;
    }

    public function getAssociationFieldName()
    {
        return $this->getName();
    }

    public function getAuditDetails(): array
    {
        return ['name' => $this->getName()];
    }
}
