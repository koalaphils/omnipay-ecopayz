<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;

/**
 * Country.
 */
class Country extends Entity implements ActionInterface, TimestampInterface, AuditInterface, AuditAssociationInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DbBundle\Entity\Currency
     */
    private $currency;

    private $tags;

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return Country
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Country
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set currency.
     *
     * @param \DbBundle\Entity\Currency $currency
     *
     * @return Country
     */
    public function setCurrency(Currency $currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return \DbBundle\Entity\Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    public function getTags(): array
    {
        if ($this->tags === null) {
            $this->tags = [];
        } elseif (!is_array($this->tags)) {
            $this->tags = [$this->tags];
        }

        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        if ($tags === null) {
            $this->tags = [];
        } else {
            $this->tags = $tags;
        }

        return $this;
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_COUNTRY;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt'];
    }

    public function getAssociationFields()
    {
        return ['currency'];
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
        return ['name' => $this->getName(), 'code' => $this->getCode()];
    }
}
