<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Interfaces\VersionInterface;

/**
 * DWL.
 */
class DWL extends Entity implements ActionInterface, TimestampInterface, VersionInterface, AuditInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    const DWL_STATUS_UPLOADED = 1;
    const DWL_STATUS_PROCESSING = 2;
    const DWL_STATUS_PROCESSED = 3;
    const DWL_STATUS_SUBMITED = 4;
    const DWL_STATUS_TRANSACTING = 5;
    const DWL_STATUS_COMPLETED = 6;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var int
     */
    private $status;

    /**
     * @var int
     */
    private $version;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var array
     */
    private $details;

    /**
     * Construct DWL.
     */
    public function __construct()
    {
        $this->setStatus(self::DWL_STATUS_UPLOADED);
        $this->setDetails([]);
        $this->setVersion(1);
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return DWL
     */
    public function setStatus($status)
    {
        $this->status = $status;
        $this->setDetail('versions.' . $this->getVersion(), $status);

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set version.
     *
     * @param int $version
     *
     * @return DWL
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    public function incrementVersion(): self
    {
        $this->version += 1;

        return $this;
    }

    /**
     * Get version.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return DWL
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set details.
     *
     * @param json $details
     *
     * @return DWL
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * Get details.
     *
     * @return array
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
     * @return DWL
     */
    public function setDetail($key, $value)
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

    public function hasDetail($key): bool
    {
        return array_has($this->details, $key);
    }

    /**
     * Set product.
     *
     * @param Product $product
     *
     * @return DWL
     */
    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product.
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set currency.
     *
     * @param Currency $currency
     *
     * @return DWL
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    public function getVersionColumn()
    {
        return 'updatedAt';
    }

    public function getVersionType()
    {
        return 'datetime';
    }

    public function getStatusAsText(): string
    {
        return $this->getStatusAsTexts()[$this->getStatus()];
    }

    public static function getStatusAsTexts(): array
    {
        return [
            DWL::DWL_STATUS_UPLOADED => 'uploaded',
            DWL::DWL_STATUS_PROCESSING => 'processing',
            DWL::DWL_STATUS_PROCESSED => 'processed',
            DWL::DWL_STATUS_SUBMITED => 'submited',
            DWL::DWL_STATUS_TRANSACTING => 'transacting',
            DWL::DWL_STATUS_COMPLETED => 'completed',
        ];
    }

    public function isNew(): bool
    {
        return $this->id === null;
    }

    public function getLastCompletedVersion(): ?int
    {
        $lastCompletedVersion = null;
        $versions = $this->getDetail('versions');
        foreach ($versions as $version => $status) {
            if ($this->isVersionCompleted($version)) {
                $lastCompletedVersion = (int) $version;
            }
        }

        return $lastCompletedVersion;
    }

    public function isVersionCompleted($version): bool
    {
        if ($this->hasVersion($version)) {
            return ((int) $this->getDetail('versions.' . $version)) === DWL::DWL_STATUS_COMPLETED;
        }

        return false;
    }

    public function hasVersion($version): bool
    {
        return $this->hasDetail('versions.' . $version);
    }

    public function getVersionStatus($version): int
    {
        return $this->getDetail('versions.' . $version);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::DWL_STATUS_COMPLETED;
    }

    public function isProcessing() : bool
    {
        return $this->status === self::DWL_STATUS_PROCESSING;
    }

    public function isProcessed(): bool
    {
        return $this->status === self::DWL_STATUS_PROCESSED;
    }

    public function isSubmitted() : bool
    {
        return $this->status === self::DWL_STATUS_SUBMITED;
    }

    public function isTransacting() : bool
    {
        return $this->status === self::DWL_STATUS_TRANSACTING;
    }

    public function setStatusCompleted() : DWL
    {
        $this->status = self::DWL_STATUS_COMPLETED;

        return $this;
    }

    /**
     * @return bool whether DWL is at any phase "higher" than processing
     */
    public function isBeyondProcessingPhase() : bool
    {
        return (
            $this->isSubmitted() ||
            $this->isTransacting() ||
            $this->isCompleted()
        );
    }

    public function canBeExported() : bool
    {
        return $this->isBeyondProcessingPhase() === true;
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_DWL;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt', 'details'];
    }

    public function getAssociationFields()
    {
        return ['currency', 'product'];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getLabel()
    {
        return sprintf('%s (%s - %s)', $this->getDate()->format('M d, Y'), $this->getProduct()->getName(), $this->getCurrency()->getCode());
    }

    public function isAudit()
    {
        return true;
    }

    public function getAuditDetails(): array
    {
        return [
            'date' => $this->getDate(),
            'currency' => $this->getCurrency(),
            'product' => $this->getProduct(),
            'version' => $this->getVersion(),
            'status' => $this->getStatus(),
        ];
    }

    public function getEncodedUpdatedAt(): string
    {
        return base64_encode($this->getUpdatedAt()->format('Y-m-d H:i:s'));
    }
}
