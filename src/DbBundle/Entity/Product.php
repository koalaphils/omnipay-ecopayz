<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;

class Product extends Entity implements ActionInterface, TimestampInterface, AuditInterface, AuditAssociationInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    public const MEMBER_WALLET_CODE = 'PWM';
    public const EVOLUTION_PRODUCT_CODE = 'EVOLUTION';
    public const AFFILIATE_WALLET_CODE = 'PW';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var int
     */
    protected $isActive;

    /**
     * @var string
     */
    protected $logo;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var json
     */
    private $details;

    private $commissionHistory;

    public function __construct()
    {
        $this->commissionHistory = new \Doctrine\Common\Collections\ArrayCollection([]);
    }

    /**
     * @var \DateTime
     */
    protected $deletedAt;

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
     * Set name.
     *
     * @param string $name Description
     *
     * @return \DbBundle\Entity\Product
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * Set code.
     *
     * @param string $code
     *
     * @return \DbBundle\Entity\CustomerProduct
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Set isActive.
     *
     * @param bool $isActive Description
     *
     * @return \DbBundle\Entity\CustomerProduct
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive.
     *
     * @return int
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    public function isActive() : bool
    {
        return $this->getIsActive() == true;
    }

    /**
     * Set Logo.
     *
     * @param type $logo
     *
     * @return Product
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get Logo.
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set Url.
     *
     * @param type $url
     *
     * @return Product
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get Url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    public function suspend()
    {
        $this->setIsActive(false);
    }

    public function activate()
    {
        $this->setIsActive(true);
    }


    /**
     * Set details.
     *
     * @param json $details
     *
     * @return Product
     */
    public function setDetails($details): self
    {
        $this->details = $details;

        return $this;
    }

    /**
     * Get details.
     *
     * @return json
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set specific detail.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return Product
     */
    public function setDetail($key, $value): self
    {
        array_set($this->details, $key, $value);

        return $this;
    }

    /**
     * Get specific detail.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getDetail($key, $default = null)
    {
        return array_get($this->details, $key, $default);
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_CURRENCY;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt', 'logo', 'details'];
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
        return ['code' => $this->getCode(), 'name' => $this->getName(), 'isActive' => $this->isActive];
    }

    public function addCommissionHistory(ProductCommission $commission): void
    {
        $this->commissionHistory->add($commission);
    }

    public function getCommissionHistory(): \Doctrine\Common\Collections\ArrayCollection
    {
        return $this->commissionHistory;
    }

    public function getCommission() : ProductCommission
    {
        $criteria = \Doctrine\Common\Collections\Criteria::create();
        $criteria->where(\Doctrine\Common\Collections\Criteria::expr()->eq('isLatest', true));
        $criteria->setFirstResult(0);
        $criteria->setMaxResults(1);

        return $this->commissionHistory->matching($criteria)->current();
    }

    /**
     * Set deletedAt
     *
     * @param \DateTime $deletedAt
     */
    public function setDeletedAt(\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return !is_null($this->getDeletedAt()) ? true : false;
    }

    public function setDateTimeToDelete($deletedAt): self
    {
        $this->setDeletedAt($deletedAt);

        return $this;
    }

    public function setUniqueCode(): self
    {
        $this->setCode($this->getCode() . '_' . $this->getId());

        return $this;
    }

    public function setUniqueName(): self
    {
        $this->setName($this->getName() . '_' . $this->getId());

        return $this;
    }

    public function isAcWallet(): bool
    {
        return $this->getDetail('ac_wallet', false);
    }

    public function hasUsername(): bool
    {
        return $this->getDetail('has_username', false);
    }

    public function canBeRequested(): bool
    {
        return $this->getDetail('can_be_requested', false);
    }

    public function hasTerms(): bool
    {
        return $this->getDetail('has_terms', false);
    }
}
