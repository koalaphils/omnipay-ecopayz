<?php

namespace DbBundle\Entity;

class AuditRevisionLog extends Entity
{
    private const DETAILS_IDENTIFIER = 'identifier';
    private const DETAILS_CLASSNAME = 'class_name';
    private const DETAILS_LABEL = 'label';
    
    const OPERATION_CREATE = 1;
    const OPERATION_UPDATE = 2;
    const OPERATION_DELETE = 3;
    const OPERATION_LOGIN = 4;
    const OPERATION_LOGOUT = 5;

    const CATEGORY_BONUS = 1;
    const CATEGORY_COUNTRY = 2;
    const CATEGORY_CURRENCY = 3;
    const CATEGORY_CUSTOMER = 4;
    const CATEGORY_CUSTOMER_GROUP = 5;
    const CATEGORY_GATEWAY = 7;
    const CATEGORY_GATEWAY_TRANSACTION = 8;
    const CATEGORY_NOTICE = 9;
    const CATEGORY_PAYMENT_OPTION = 10;
    const CATEGORY_PRODUCT = 11;
    const CATEGORY_CUSTOMER_TRANSACTION_DEPOSIT = 12;
    const CATEGORY_USER = 13;
    const CATEGORY_USER_GROUP = 14;
    const CATEGORY_LOGIN = 15;
    const CATEGORY_LOGOUT = 16;
    const CATEGORY_CUSTOMER_PRODUCT = 17;
    const CATEGORY_CUSTOMER_PAYMENT_OPTION = 18;
    const CATEGORY_AFFILIATE = 4;
    const CATEGORY_CUSTOMER_TRANSACTION_WITHDRAWAL = 20;
    const CATEGORY_CUSTOMER_TRANSACTION_TRANSFER = 21;
    const CATEGORY_CUSTOMER_TRANSACTION_P2P_TRANSFER = 22;
    const CATEGORY_CUSTOMER_TRANSACTION_DWL = 23;
    const CATEGORY_CUSTOMER_TRANSACTION_BONUS = 25;
    const CATEGORY_RISK_SETTING = 26;
    const CATEGORY_MEMBER_BANNER = 27;
    const CATEGORY_MEMBER_WEBSITE = 28;
    const CATEGORY_MEMBER_REFERRAL_NAME = 29;
    const CATEGORY_BANNER_IMAGE = 30;
    const CATEGORY_RUNNING_COMMISSION = 31;
    const CATEGORY_CUSTOMER_TRANSACTION_COMMISSION = 32;
    const CATEGORY_BITCOIN_RATE_SETTING = 33;
    const CATEGORY_MEMBER_TRANSACTION_DEBIT_ADJUSTMENT = 34;
    const CATEGORY_MEMBER_TRANSACTION_CREDIT_ADJUSTMENT = 35;
    const CATEGORY_RUNNING_REVENUE_SHARE = 36;
    const CATEGORY_CUSTOMER_TRANSACTION_REVENUE_SHARE = 37;
    const CATEGORY_MEMBER_REQUEST_KYC = 39;

    private $details;

    private $operation;

    private $category;

    private $auditRevision;
    
    private $className;
    
    private $label;
    
    private $identifier;

    public function __construct()
    {
        $this->setDetails([]);
    }

    public function setDetails($details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function setOperation($operation): self
    {
        $this->operation = $operation;

        return $this;
    }

    public function getOperation(): int
    {
        return $this->operation;
    }

    public function setCategory($category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getCategory(): int
    {
        return $this->category;
    }

    public function setAuditRevision(AuditRevision $auditRevision): self
    {
        $this->auditRevision = $auditRevision;

        return $this;
    }

    public function getAuditRevision():? AuditRevision
    {
        return $this->auditRevision;
    }

    public function setDetail($key, $value)
    {
        array_set($this->details, $key, $value);

        return $this;
    }

    public function getDetail($key, $default = null)
    {
        return array_get($this->details, $key, $default);
    }
    
    public function hasDetail(string $key): bool
    {
        return array_has($this->details, $key);
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function getFields():? array
    {
        return $this->getDetail('fields');
    }

    public function getEntityDetails(): array
    {
        return $this->getDetail('details', []);
    }

    public function getCategoryKey(): string
    {
        $key = '';

        switch ($this->getCategory()) {
            case self::CATEGORY_BONUS:
                $key = 'bonus';
                break;
            case self::CATEGORY_COUNTRY:
                $key = 'country';
                break;
            case self::CATEGORY_CURRENCY:
                $key = 'currency';
                break;
            case self::CATEGORY_CUSTOMER:
                $key = 'customer';
                break;
            case self::CATEGORY_CUSTOMER_GROUP:
                $key = 'customerGroup';
                break;
            case self::CATEGORY_GATEWAY:
                $key = 'gateway';
                break;
            case self::CATEGORY_GATEWAY_TRANSACTION:
                $key = 'gatewayTransaction';
                break;
            case self::CATEGORY_NOTICE:
                $key = 'notice';
                break;
            case self::CATEGORY_PAYMENT_OPTION:
                $key = 'paymentOption';
                break;
            case self::CATEGORY_PRODUCT:
                $key = 'product';
                break;
            case self::CATEGORY_CUSTOMER_TRANSACTION_DEPOSIT:
                $key = 'deposit';
                break;
            case self::CATEGORY_USER:
                $key = 'user';
                break;
            case self::CATEGORY_USER_GROUP:
                $key = 'userGroup';
                break;
            case self::CATEGORY_LOGIN:
                $key = 'login';
                break;
            case self::CATEGORY_LOGOUT:
                $key = 'logout';
                break;
            case self::CATEGORY_CUSTOMER_PRODUCT:
                $key = 'customerProduct';
                break;
            case self::CATEGORY_CUSTOMER_PAYMENT_OPTION:
                $key = 'customerPaymentOption';
                break;
            case self::CATEGORY_AFFILIATE:
                $key = 'affiliate';
                break;
            case self::CATEGORY_CUSTOMER_TRANSACTION_WITHDRAWAL:
                $key = 'withdrawal';
                break;
            case self::CATEGORY_CUSTOMER_TRANSACTION_TRANSFER:
                $key = 'transfer';
                break;
            case self::CATEGORY_CUSTOMER_TRANSACTION_P2P_TRANSFER:
                $key = 'p2pTransfer';
                break;
            case self::CATEGORY_CUSTOMER_TRANSACTION_BONUS:
                $key = 'transactionBonus';
                break;
            case self::CATEGORY_RISK_SETTING:
                $key = 'riskSetting';
                break;
            case self::CATEGORY_MEMBER_BANNER:
                $key = 'memberBanner';
                break;
            case self::CATEGORY_MEMBER_WEBSITE:
                $key = 'memberWebsite';
                break;
            case self::CATEGORY_MEMBER_REFERRAL_NAME:
                $key = 'memberReferralName';
                break;
            case self::CATEGORY_BANNER_IMAGE:
                $key = 'bannerImage';
                break;
            case self::CATEGORY_CUSTOMER_TRANSACTION_COMMISSION:
                $key = 'commission';
                break;
            case self::CATEGORY_BITCOIN_RATE_SETTING;
                $key = 'bitcoinRateSetting';
                break;
            case self::CATEGORY_MEMBER_TRANSACTION_DEBIT_ADJUSTMENT:
                $key = 'debitAdjustment';
                break;
            case self::CATEGORY_MEMBER_TRANSACTION_CREDIT_ADJUSTMENT:
                $key = 'creditAdjustment';
                break;
            case self::CATEGORY_RUNNING_REVENUE_SHARE:
                $key = 'revenueShare';
                break;
            case self::CATEGORY_CUSTOMER_TRANSACTION_REVENUE_SHARE:
                $key = 'cRevenueShare';
                break;
        }

        return $key;
    }

    public function getOperationKey(): string
    {
        $key = '';

        switch ($this->getOperation()) {
            case self::OPERATION_CREATE:
                $key = 'create';
                break;
            case self::OPERATION_UPDATE:
                $key = 'update';
                break;
            case self::OPERATION_DELETE:
                $key = 'delete';
                break;
            case self::OPERATION_LOGIN:
                $key = 'login';
                break;
            case self::OPERATION_LOGOUT:
                $key = 'logout';
                break;
        }

        return $key;
    }
    
    public function getClassName(): ?string
    {
        return $this->className;
    }
    
    public function setClassName(?string $className): self
    {
        $this->setDetail(self::DETAILS_CLASSNAME, $className);
        $this->className = $className;
        
        return $this;
    }
    
    public function setIdentifier(?string $identifier): self
    {
        $this->setDetail(self::DETAILS_IDENTIFIER, $identifier);
        
        return $this;
    }
    
    public function setLabel(?string $label): self
    {
        $this->setDetail(self::DETAILS_LABEL, $label);
        
        return $this;
    }
}
