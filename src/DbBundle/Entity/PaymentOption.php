<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;

/**
 * PaymentOption
 */
class PaymentOption extends Entity implements ActionInterface, TimestampInterface, AuditInterface, AuditAssociationInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    const PAYMENT_MODE_ECOPAYZ = 'ecopayz';
    const PAYMENT_MODE_BITCOIN = 'bitcoin';
    const PAYMENT_MODE_OFFLINE = 'offline';
    const FIELD_CODE_REQUIRED = 'isRequired';
    const FIELD_CODE_UNIQUE = 'isUnique';

    const CONFIG_AUTODECLINE_STATUS= 'autoDecline.status';
    const CONFIG_AUTODECLINE_TYPES= 'autoDecline.types';
    const CONFIG_AUTODECLINE_INTERVAL= 'autoDecline.interval';

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var string
     */
    private $image;

    /**
     * @var bool
     */
    private $isActive;

    /**
     * @var bool
     */
    private $autoDecline;

    /**
     * @var string
     */
    private $sort;

    /**
     * @var array
     */
    private $configurations;

    /**
     * payment mode will be use for geting the
     * type of payment use in payum, (eg. ecopayz, offline)
     *
     * @var string
     */
    private $paymentMode = self::PAYMENT_MODE_OFFLINE;

    public function __construct()
    {
        $this->setCode('');
        $this->setName('');
        $this->setFields([]);
        $this->setImage(null);
        $this->setIsActive(false);
        $this->setAutoDecline(false);
        $this->configurations = [];
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function setIsActive($isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getAutoDecline(): bool
    {
        return $this->autoDecline;
    }

    public function setAutoDecline($autoDecline): self
    {
        $this->autoDecline = $autoDecline;

        return $this;
    }

    public function suspend()
    {
        $this->setIsActive(false);
    }

    public function enable()
    {
        $this->setIsActive(true);
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper($code);

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function setImage(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_PAYMENT_OPTION;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt', 'image'];
    }

    public function getAssociationFields()
    {
        return [];
    }

    public function getIdentifier()
    {
        return $this->getCode();
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

    public function setPaymentMode(string $paymentMode): self
    {
        $this->paymentMode = $paymentMode;

        return $this;
    }

    /**
     * Get the payment mode available for payum
     * This will return ecopayz or offline as string
     *
     * @return string
     */
    public function getPaymentMode(): string
    {
        return $this->paymentMode;
    }

    public function sortFieldsAscending(): array
    {
        $fields = $this->getFields();
        usort($fields, function ($field1, $filed2) {
            return ($field1['order'] ?? 1) <=> ($filed2['order'] ?? 2);
        });
        $this->setFields($fields);

        return $fields;
    }

    public function getAuditDetails(): array
    {
        return ['name' => $this->getName(), 'paymentMode' => $this->getPaymentMode(), 'isActive' => $this->getIsActive(), 'autoDecline' => $this->getAutoDecline()];
    }

    public function isPaymentModeOffline(): bool
    {
        return $this->getPaymentMode() == self::PAYMENT_MODE_OFFLINE ? true : false;
    }
    
    public function hasAutoDecline(): bool
    {
        return $this->getAutoDecline() ? true : false;
    }

    public function getSort(): string
    {
        if (is_null($this->sort)) {
            $this->sort = '';
        }

        return $this->sort;
    }

    public function setSort(?string $sort): self
    {
        if (is_null($sort)) {
            $this->sort = '';
        } else {
            $this->sort = $sort;
        }

        return $this;
    }

    public function isPaymentEcopayz(): bool
    {
        return $this->getPaymentMode() == self::PAYMENT_MODE_ECOPAYZ ? true : false;
    }

    public function isPaymentBitcoin(): bool
    {
        return $this->getPaymentMode() === self::PAYMENT_MODE_BITCOIN;
    }

    public function isBitcoinHasEnabledAutoDecline(): bool
    {
        return $this->isPaymentBitcoin() && $this->hasAutoDecline();
    }

    public function getCodeOfRequiredField(): array
    {
        return $this->getCodeOfFieldByRequirement(self::FIELD_CODE_REQUIRED);
    }

    public function getCodeOfUniqueField(): array
    {
        return $this->getCodeOfFieldByRequirement(self::FIELD_CODE_UNIQUE);
    }

    public function hasRequiredField(string $requiredField): bool
    {
        $fieldDetails = $this->getCodeOfRequiredField();   
        $field = array_filter($fieldDetails, function($codes) use ($requiredField) {       
            return $codes == $requiredField;
        });
        
        return !empty($field) ? true : false;
    }

    public function hasUniqueField(string $uniqueField): bool
    {
        $fieldDetails = $this->getCodeOfUniqueField();
        $field = array_filter($fieldDetails, function($codes) use ($uniqueField) {       
            return $codes == $uniqueField;
        });
        
        return !empty($field) ? true : false;
    }

    private function getCodeOfFieldByRequirement(string $requirement): array
    {
        $codeField = [];
        $fields = $this->getFields();
        foreach ($fields as $key) {
            if (array_get($key, $requirement, false)) {
                $codeField[] = $key['code'];
            }
        }
        
        return $codeField;
    }

    public function getConfigurations(): array
    {
        if ($this->configurations === null) {
            $this->configurations = [];
        }

        return $this->configurations;
    }

    public function setConfigurations(?array $configurations): self
    {
        if ($configurations === null) {
            $this->configurations = [];
        } else {
            $this->configurations = $configurations;
        }

        return $this;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getConfiguration(string $key, $default = null)
    {
        if ($this->configurations === null) {
            $this->configurations = [];
        }

        return array_get($this->configurations, $key, $default);
    }

    /**
     * @param string $key
     * @param mixed|null $value
     * @return PaymentOption
     */
    public function setConfiguration(string $key, $value): self
    {
        if ($this->configurations === null) {
            $this->configurations = [];
        }

        array_set($this->configurations, $key, $value);

        return $this;
    }
}
