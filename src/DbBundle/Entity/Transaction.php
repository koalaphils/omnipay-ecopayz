<?php

namespace DbBundle\Entity;

use AppBundle\ValueObject\Money;
use AppBundle\ValueObject\Number;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Interfaces\VersionInterface;
use DbBundle\Entity\Traits\ActionTrait;
use DbBundle\Entity\Traits\SoftDeleteTrait;
use DbBundle\Entity\Traits\TimestampTrait;
use DbBundle\Entity\Product;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;

/**
 * Transaction.
 */
class Transaction extends Entity implements ActionInterface, TimestampInterface, VersionInterface, AuditInterface
{
    use ActionTrait;
    use SoftDeleteTrait;
    use TimestampTrait;

    public const DETAIL_BITCOIN_INFO = 'bitcoin';
    public const DETAIL_BITCOIN_ADDRESS = 'bitcoin.receiver_unique_address';
    public const DETAIL_BITCOIN_BLOCKCHAIN_RATE = 'bitcoin.block_chain_rate';
    public const DETAIL_BITCOIN_RATE = 'bitcoin.rate';
    public const DETAIL_BITCOIN_TOTAL_REQUESTED_BTC = 'bitcoin.total_requested_btc';
    public const DETAIL_BITCOIN_RATE_DETAIL = 'bitcoin.rate_detail';
    public const DETAIL_BITCOIN_CONFIRMATION = 'bitcoin.confirmation_count';
    public const DETAIL_BITCOIN_STATUS = 'bitcoin.request_status';
    public const DETAIL_BITCOIN_CALLBACK = 'bitcoin.callback';
    public const DETAIL_BITCOIN_BLOCKCHAIN_INDEX = 'bitcoin.blokchain_index';
    public const DETAIL_BITCOIN_TRANSACTION = 'bitcoin.transaction';
    public const DETAIL_BITCOIN_TRANSACTION_HASH = 'bitcoin.transaction.hash';
    public const DETAIL_BITCOIN_TRANSACTION_VALUE = 'bitcoin.transaction.value';
    public const DETAIL_BITCOIN_TRANSACTION_VALUE_SATOSHI = 'bitcoin.transaction.value_satoshi';
    public const DETAIL_BITCOIN_ACKNOWLEDGED_BY_USER = 'bitcoin.acknowledged_by_user';
    public const DETAIL_BITCOIN_STATUS_PENDING = 'pending_confirmation';
    public const DETAIL_BITCOIN_STATUS_CONFIRMED = 'confirmed';
    public const DETAIL_BITCOIN_RATE_EXPIRED = 'bitcoin.rate_expired';
    private const DETAIL_BITCOIN_TRANSACTION_SENDER_ADDRESS = 'bitcoin.transaction.sender_address';
    private const DETAIL_BITCOIN_STATUS_NO_CONFIRMATION = 'no_confirmation';

    private const DETAIL_COMMISSION_CONVERTION_RATE = 'commission.convertion.rate';
    private const DETAIL_COMMISSION_PRODUCT_PERCENTAGE = 'commission.product.percentage';
    private const DETAIL_COMMISSION_COMPUTED = 'commission.computed';
    private const DETAIL_COMMISSION_CONVERTIONS = 'commission.convertions';
    private const DETAIL_DWL_ID = 'dwl.id';
    private const DETAIL_FILE_NAME = 'file.name';
    private const DETAIL_FILE_FOLDER = 'file.folder';
    private const FILE_DAY_LIMIT = 10;

    const TRANSACTION_TYPE_DEPOSIT = 1;
    const TRANSACTION_TYPE_WITHDRAW = 2;
    const TRANSACTION_TYPE_TRANSFER = 3;
    const TRANSACTION_TYPE_BONUS = 4;
    const TRANSACTION_TYPE_P2P_TRANSFER = 5;
    const TRANSACTION_TYPE_DWL = 6;
    const TRANSACTION_TYPE_COMMISSION = 7;
    const TRANSACTION_TYPE_BET = 8;
    const TRANSACTION_TYPE_ADJUSTMENT = 9;
    const TRANSACTION_TYPE_DEBIT_ADJUSTMENT = 10;
    const TRANSACTION_TYPE_CREDIT_ADJUSTMENT = 11;
    const FILE_FOLDER_DIR = 'transaction';

    const TRANSACTION_STATUS_START = 1;
    const TRANSACTION_STATUS_END = 2;
    const TRANSACTION_STATUS_DECLINE = 3;
    const TRANSACTION_STATUS_ACKNOWLEDGE = 4;
    const TRANSACTION_STATUS_VOIDED = 'voided';

    private $number;
    private $currency;
    private $amount = 0;
    private $fees;
    private $type;
    private $date;
    private $status;
    private $isVoided = false;
    private $details;
    private $subTransactions;
    private $customer;
    private $gateway;
    private $paymentOption;
    private $paymentOptionOnTransaction;
    private $paymentOptionType;
    private $creator;
    private $toCustomer;
    private $dwlId;
    private $betId;
    private $betEventId;
    private $commissionComputedOriginal;
    private $immutablePaymentOptionData;
    private $finishedAt;
    private $virtualBitcoinTransactionHash;
    private $bitcoinConfirmationCount;
    private $virtualBitcoinSenderAddress;
    private $virtualBitcoinReceiverUniqueAddress;
    private $product;
    private $productID;
    private $customerID;
    private $email;
    private $popup;

    /**
     * @var null|int
     */
    private $bitcoinConfirmation;

    public function __construct()
    {
        $this->setFees([]);
        $this->setStatus(self::TRANSACTION_STATUS_START);
        $this->subTransactions = new ArrayCollection();
        $this->setDetails([]);
    }

    /**
     * Set number.
     *
     * @param string $number
     *
     * @return Transaction
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number.
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set currency.
     *
     * @param string $currency
     *
     * @return Transaction
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set amount.
     *
     * @param string $amount
     *
     * @return Transaction
     */
    public function setAmount($amount = 0)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount.
     *
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set fees.
     *
     * @param json $fees
     *
     * @return Transaction
     */
    public function setFees($fees)
    {
        $this->fees = $fees;

        return $this;
    }

    /**
     * Get fees.
     *
     * @return json
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * Set specific fee.
     *
     * @param string          $key
     * @param int|float|float $fee
     *
     * @return Transaction
     */
    public function setFee($key, $fee)
    {
        array_set($this->fees, $key, $fee);

        return $this;
    }

    /**
     * Get specific fee.
     *
     * @param string          $key
     * @param int|float|float $default
     *
     * @return int|float|float
     */
    public function getFee($key, $default = 0)
    {
        return array_get($this->fees, $key, $default);
    }

    public function hasFee($key)
    {
        return array_has($this->fees, $key);
    }

    /**
     * Set type.
     *
     * @param tinyint $type
     *
     * @return Transaction
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): int
    {
        return (int) $this->type;
    }

    public function getTypeText()
    {
        return $this->getTypesText()[$this->getType()];
    }

    public function getTypesText(): array
    {
        return [
            static::TRANSACTION_TYPE_DEPOSIT => 'deposit',
            static::TRANSACTION_TYPE_WITHDRAW => 'withdraw',
            static::TRANSACTION_TYPE_TRANSFER => 'transfer',
            static::TRANSACTION_TYPE_P2P_TRANSFER => 'p2p_transfer',
            static::TRANSACTION_TYPE_BONUS => 'bonus',
            static::TRANSACTION_TYPE_DWL => 'dwl',
            static::TRANSACTION_TYPE_BET => 'bet',
            static::TRANSACTION_TYPE_COMMISSION => 'commission',
            static::TRANSACTION_TYPE_ADJUSTMENT => 'adjustment',
            static::TRANSACTION_TYPE_DEBIT_ADJUSTMENT => 'debit_adjustment',
            static::TRANSACTION_TYPE_CREDIT_ADJUSTMENT => 'credit_adjustment',
        ];
    }

    /**
     * Set date.
     *
     * @param DateTime $date
     *
     * @return Transaction
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set status.
     *
     * @param tinyint $status
     *
     * @return Transaction
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return tinyint
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set isVoided.
     *
     * @param tinyint $isVoided
     *
     * @return Transaction
     */
    public function setIsVoided($isVoided = false)
    {
        $this->isVoided = $isVoided;

        return $this;
    }

    /**
     * Get isVoided.
     *
     * @return tinyint
     */
    public function getIsVoided()
    {
        return $this->isVoided;
    }

    /**
     * Check if voided.
     *
     * @return type
     */
    public function isVoided()
    {
        return $this->getIsVoided();
    }

    public function isDeclined() : bool
    {
        return $this->getStatus() === Transaction::TRANSACTION_STATUS_DECLINE;
    }

    /**
     * Set details.
     *
     * @param json $details
     *
     * @return Transaction
     */
    public function setDetails($details)
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
     * @return Transaction
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

    /**
     * Get sub transaction.
     *
     * @return ArrayCollection
     */
    public function getSubTransactions()
    {
        return $this->subTransactions;
    }

    public function getFirstSubTransaction(): ?SubTransaction
    {
        if (empty($this->subTransactions)) {
            return null;
        }

        if ($this->subTransactions instanceof ArrayCollection) {
            return $this->subTransactions->get(0);
        }

        return $this->subTransactions[0];
    }

    /**
     * Add sub transaction.
     *
     * @param SubTransaction $sub
     *
     * @return Transaction
     */
    public function addSubTransaction(SubTransaction $sub)
    {
        $this->subTransactions->add($sub);
        $sub->setParent($this);

        return $this;
    }

    /**
     * Add sub transaction.
     *
     * @param SubTransaction $subs
     *
     * @return Transaction
     */
    public function setSubTransactions($subs = [])
    {
        foreach ($subs as $sub) {
            $this->subTransactions->add($sub);
            $sub->setParent($this);
        }

        return $this;
    }

    /**
     * Get Customer.
     *
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Set Customer.
     *
     * @param Customer $customer
     *
     * @return Transaction
     */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Get gateway.
     *
     * @return Gateway
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * Set gateway.
     *
     * @param Gateway $gateway
     *
     * @return Transaction
     */
    public function setGateway($gateway = null)
    {
        $this->gateway = $gateway;

        return $this;
    }

    public function getVersionColumn()
    {
        return 'updatedAt';
    }

    public function getVersionType()
    {
        return 'datetime';
    }

    public function getFinishedAt(): ?DateTimeImmutable
    {
        if ($this->finishedAt instanceof DateTime) {
            $this->finishedAt = DateTimeImmutable::createFromMutable($this->finishedAt);
        }

        return $this->finishedAt;
    }

    public function setFinishedAt(DateTimeInterface $finishedAt): self
    {
        if ($finishedAt instanceof DateTimeImmutable) {
            $this->finishedAt = $finishedAt;
        } elseif ($finishedAt instanceof DateTime) {
            $this->finishedAt = DateTimeImmutable::createFromMutable($finishedAt);
        } else {
            throw new UnexpectedValueException(sprintf(
                'Finished At must be type of %s or %s only.',
                DateTime::class,
                DateTimeImmutable::class
            ));
        }

        return $this;
    }

    public function getPaymentOption(): ?CustomerPaymentOption
    {
        return $this->paymentOption;
    }


    public function setPaymentOption($paymentOption)
    {
        $this->paymentOption = $paymentOption;

        return $this;
    }

    public function getPaymentOptionOnTransaction(): ?CustomerPaymentOption
    {
        return $this->paymentOptionOnTransaction;
    }


    public function setPaymentOptionOnTransaction($paymentOptionOnTransaction)
    {
        $this->paymentOptionOnTransaction = $paymentOptionOnTransaction;

        return $this;
    }

    // save data that should not be changed even if the records at the related tables change
    // this is a poor attempt to have historical data while auditlog is still not in place
    public function retainImmutableData()
    {
        if (!$this->isClosedForFurtherProcessing()) {
            $this->copyImmutableCustomerProductData();
            $this->setImmutableCustomerReferrer();
            if ($this->isWithdrawal() || $this->isDeposit()) {
                $this->setImmutablePaymentOptionData();
                $this->setImmutablePaymentOptionOnTransactionData();
            }
        }
    }

    public function retainImmutableDataForDWL(): void
    {
        if ($this->isDwl()) {
            $this->copyImmutableCustomerProductData();
        }
    }


    public function isClosedForFurtherProcessing() : bool
    {
        return ($this->hasEnded() || $this->isDeclined() || $this->isVoided());
    }

    public function setImmutableCustomerReferrer(): void
    {
        if ($this->getCustomer()->getAffiliate() instanceof Customer) {
            $this->setDetail('customer.referrer', [
                'id' => $this->getCustomer()->getAffiliate()->getId(),
                'f_name' => $this->getCustomer()->getAffiliate()->getFName(),
                'l_name' => $this->getCustomer()->getAffiliate()->getLName(),
                'user' => ['username' => $this->getCustomer()->getAffiliate()->getUser()->getUsername()],
                'full_name' => $this->getCustomer()->getAffiliate()->getFullName(),                
                'product_username' => $this->getCustomer()->getPinUserCode()
            ]);
        }
    }

    public function getImmutableCustomerReferrer(): array
    {
        return $this->getDetail('customer.referrer');
    }

    public function hasImmutableCustomerReferrer(): bool
    {
        return $this->hasDetail('customer.referrer');
    }

    public function hasDetail(string $key): bool
    {
        return array_has($this->details, $key);
    }

    public function isTransactionPaymentBitcoin(): bool
    {
        $paymentOption = $this->paymentOption;

        if (is_null($paymentOption)) {
            return false;
        }

        return $paymentOption->getPaymentOption()->isPaymentBitcoin();
    }

    /**
     * to track the data of customer's payment option
     * (normally starting the time of transaction creation)
     */
    public function setImmutablePaymentOptionData()
    {
        $this->setDetail('paymentOption.email', array_get($this->paymentOption->getFields(),'email'));
        $this->setDetail('paymentOption.code', $this->paymentOption->getPaymentOption()->getCode());
        $this->setDetail('paymentOption.name', $this->paymentOption->getPaymentOption()->getName());

        $paymentOption = $this->paymentOption->getPaymentOption();

        if ($paymentOption->isPaymentEcopayz() || $paymentOption->isPaymentBitcoin()) {
            $this->setDetail('paymentOption.accountId', array_get($this->paymentOption->getFields(),'account_id'));
        }
    }

    /**
     * Set the payment option on transaction data
     * only on creation of transactions from member site
     */
    public function setImmutablePaymentOptionOnTransactionData()
    {
        if ($paymentOptionOnTransaction = $this->getPaymentOptionOnTransaction()) {
            $this->setDetail('paymentOptionOnTransaction.email', array_get($paymentOptionOnTransaction->getFields(),'email'));
            $this->setDetail('paymentOptionOnTransaction.code', $paymentOptionOnTransaction->getPaymentOption()->getCode());
            $this->setDetail('paymentOptionOnTransaction.name', $paymentOptionOnTransaction->getPaymentOption()->getName());

            if ($paymentOptionOnTransaction->getPaymentOption()->isPaymentEcopayz() || $paymentOptionOnTransaction->getPaymentOption()->isPaymentBitcoin()) {
                $this->setDetail('paymentOptionOnTransaction.accountId', array_get($paymentOptionOnTransaction->getFields(),'account_id'));
            }
        }
    }

    /**
     * @return String the PaymentOption data info during the time that the transaction was created / requested
     * this may not be the actual payment option used to process/finalize the transaction
     */
    public function getImmutablePaymentOptionData() : string
    {
        $email = $this->getDetail('paymentOption.email');
        $paymentOptionCode = $this->getDetail('paymentOption.code');
        $paymentOption = $this->getPaymentOption();
        $label = $paymentOptionCode. ' ('. $email .')';

        if (!is_null($paymentOption)) {
            if ($paymentOption->getPaymentOption()->isPaymentEcopayz()) {
                $accountId = $this->getDetail('paymentOption.accountId');
                $label = $paymentOptionCode. ' ('. $accountId .')';
            } elseif ($this->isTransactionPaymentBitcoin()) {
                $label = $paymentOptionCode. ' ('. $this->getBitcoinAddress() .')';
            }
        }

        return $label;
    }

    public function getImmutablePaymentOptionOnTransactionData() : String
    {
        $email = $this->getDetail('paymentOptionOnTransaction.email');
        $paymentOptionCode = $this->getDetail('paymentOptionOnTransaction.code');
        $paymentOptionOnTransaction = $this->getPaymentOptionOnTransaction();
        $label = $paymentOptionCode. ' ('. $email .')';

        if (!is_null($paymentOptionOnTransaction)) {
            $paymentOption = $this->getPaymentOptionOnTransaction()->getPaymentOption();

            if ($paymentOption->isPaymentEcopayz() || $paymentOption->isPaymentBitcoin()) {
                $accountId = $this->getDetail('paymentOptionOnTransaction.accountId');
                $label = $paymentOptionCode. ' ('. $accountId .')';
            }
        }

        return $label;
    }

    public function copyImmutableCustomerProductData()
    {
        foreach ($this->getSubTransactions() as $subTransaction) {
            $subTransaction->copyImmutableCustomerProductData();
        }
    }

    public function setPaymentOptionType($paymentOptionType): self
    {
        $this->paymentOptionType = $paymentOptionType;

        return $this;
    }

    public function getPaymentOptionType(): ?PaymentOption
    {
        $this->autoSetPaymentOptionType();

        return $this->paymentOptionType;
    }

    public function getCustomerAmount()
    {        
        // zimi
        // return $this->getDetail('summary.customer_amount', $this->getDetail('summary.total_amount', 0));
        return $this->getAmount();
    }

    public function canAutoSetPaymentOptionType()
    {
        return $this->paymentOptionType === null && $this->paymentOption !== null;
    }

    public function autoSetPaymentOptionType(): self
    {
        if ($this->canAutoSetPaymentOptionType()) {
            $this->setPaymentOptionType($this->getPaymentOption()->getPaymentOption());
        }

        return $this;
    }
    public function isEnd(): bool
    {
        return $this->getStatus() === Transaction::TRANSACTION_STATUS_END;
    }

    public function hasEnded() : bool
    {
        return $this->isEnd() === true;
    }

    public function isNew(): bool
    {
        return $this->id === null;
    }

    public function isDeposit() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_DEPOSIT;
    }

    public function isWithdrawal() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_WITHDRAW;
    }

    public function isTransfer() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_TRANSFER;
    }

    public function isP2pTransfer() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_P2P_TRANSFER;
    }

    public function isBonus() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_BONUS;
    }

    public function isDwl() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_DWL;
    }

    public function isBet() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_BET;
    }

    public function isCommission(): bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_COMMISSION;
    }

    public function isDebitAdjustment(): bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_DEBIT_ADJUSTMENT;
    }

    public function isCreditAdjustment(): bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_CREDIT_ADJUSTMENT;
    }
    
    public function isAdjustment(): bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_ADJUSTMENT;
    }

    public function hasAdjustment(): bool
    {
        return $this->isAdjustment() || $this->isDebitAdjustment() || $this->isCreditAdjustment();
    }

    public function isInProgress() : bool
    {
        $statusesThatAreNotInProgress = [
            self::TRANSACTION_STATUS_START,
            self::TRANSACTION_STATUS_END,
            self::TRANSACTION_STATUS_DECLINE,
        ];

        return (!in_array($this->getStatus(), $statusesThatAreNotInProgress)) && !$this->isVoided();
    }

    public function isStart(): bool
    {
        return $this->getStatus() === Transaction::TRANSACTION_STATUS_START;
    }

    public function hasDepositUsingBitcoin(): bool
    {
        return !is_null($this->getIdentifier())
            && $this->isDeposit()
            && $this->isPaymentBitcoin();
    }

    public function isPaymentBitcoin(): bool
    {
        if ($this->paymentOption === null) {
            return false;
        }

        return $this->paymentOption->getPaymentOption()->isPaymentBitcoin();
    }

    public function isBitcoinStatusPendingConfirmation(): bool
    {
        if ( !$this->isDeclined() && !$this->isVoided()){
           return $this->hasDepositUsingBitcoin()  && $this->isBitcoinPendingOnConfirmation();
        }

        return false;
    }

    public function isBitcoinStatusConfirmed(): bool
    {
        if( !$this->isDeclined() && !$this->isVoided()){
            return $this->hasDepositUsingBitcoin() && !$this->hasEnded() && $this->isBitcoinConfirmedOnConfirmation();
        }

        return false;
    }

    public function getTypeAsText()
    {
        return $this->getTypeAsTexts()[$this->getType()];
    }

    public function getTypeAsTexts(): array
    {
        return [
            self::TRANSACTION_TYPE_DEPOSIT => 'Deposit',
            self::TRANSACTION_TYPE_WITHDRAW => 'Withdraw',
            self::TRANSACTION_TYPE_TRANSFER => 'Transfer',
            self::TRANSACTION_TYPE_P2P_TRANSFER => 'P2P Transfer',
            self::TRANSACTION_TYPE_BET => 'bet',
            self::TRANSACTION_TYPE_DWL => 'dwl',
            self::TRANSACTION_TYPE_BONUS => 'bonus',
            self::TRANSACTION_TYPE_COMMISSION => 'commission',
            self::TRANSACTION_TYPE_DEBIT_ADJUSTMENT => 'Debit',
            self::TRANSACTION_TYPE_CREDIT_ADJUSTMENT => 'Credit',
        ];
    }

    public function getCreator() : ?User
    {
        return $this->creator;
    }

    public function setCreator(User $creator)
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCategory()
    {
        if ($this->isDeposit()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DEPOSIT;
        } elseif ($this->isWithdrawal()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_WITHDRAWAL;
        } elseif ($this->isTransfer()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_TRANSFER;
        } elseif ($this->isP2pTransfer()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_P2P_TRANSFER;
        } elseif ($this->isDwl()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DWL;
        } elseif ($this->isBet()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_BET;
        } elseif ($this->isBonus()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_BONUS;
        } elseif ($this->isCommission()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_COMMISSION;
        } elseif ($this->isDebitAdjustment()) {
            $category = AuditRevisionLog::CATEGORY_MEMBER_TRANSACTION_DEBIT_ADJUSTMENT;
        } elseif ($this->isCreditAdjustment()) {
            $category = AuditRevisionLog::CATEGORY_MEMBER_TRANSACTION_CREDIT_ADJUSTMENT;
        }

        return $category;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt', 'creator'];
    }

    public function getAssociationFields()
    {
        return ['currency', 'customer', 'gateway', 'paymentOption'];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getLabel()
    {
        return $this->getNumber();
    }

    public function isAudit()
    {
        return true;
    }

    public function getToCustomer(): ?int
    {
        return $this->toCustomer;
    }

    public function getDwlId(): ?int
    {
        if (is_null($this->dwlId) && $this->hasDetail(self::DETAIL_DWL_ID)) {
            $this->dwlId = $this->getDetail(self::DETAIL_DWL_ID);
        }

        return $this->dwlId;
    }

    public function setDwlId(int $dwlId): self
    {
        $this->setDetail(self::DETAIL_DWL_ID, $dwlId);
        $this->dwlId = $dwlId;

        return $this;
    }

    public function getBetId(): ?int
    {
        return $this->betId;
    }

    public function getBetEventId(): ?int
    {
        return $this->betEventId;
    }

    public function getCommissionComputedOriginal(): ?float
    {
        return $this->commissionComputedOriginal;
    }

    public function getVirtualBitcoinTransactionHash(): ?string
    {
        return $this->virtualBitcoinTransactionHash;
    }

    public function getBitcoinConfirmationCount(): ?int
    {
        return $this->bitcoinConfirmationCount;
    }

    public function getVirtualBitcoinSenderAddress(): ?array
    {
        return $this->virtualBitcoinSenderAddress;
    }

    public function setGatewayComputedAmount($amount): Transaction
    {
        $this->setDetail('paymentGateway.computed_amount', $amount);

        return $this;
    }

    public function getAuditDetails(): array
    {
        return [
            'number' => $this->getNumber(),
            'amount' => $this->getAmount(),
            'type' => $this->getType(),
            'date' => $this->getDate(),
            'status' => $this->getStatus(),
            'isVoided' => $this->getIsVoided(),
            'toCustomer' => $this->getToCustomer(),
        ];
    }

    public function getVoidingReason(): ?string
    {
        return $this->getDetail('reasonToVoidOrDecline');
    }

    public function getNotesInDetails(): ?string
    {
        return $this->getDetail('notes');
    }

    public function setReasonToVoidOrDecline($reason)
    {
        $this->setDetail('reasonToVoidOrDecline', $reason);

        return $this;
    }

    public function setImmutablePaymentGatewayFormula(): void
    {
        if ($this->getGateway() instanceof Gateway) {
            $method = $this->getGateway()->getMethodForTransactionType($this->getTypeText());
            $this->setDetail('paymentGateway.equation', $method['equation']);
            $this->setDetail('paymentGateway.variables', $method['varialbles'] ?? []);
        }
    }

    public function decline(): self
    {
        $this->setStatus(self::TRANSACTION_STATUS_DECLINE);

        return $this;
    }

    public function void(): self
    {
        $this->setIsVoided(true);

        return $this;
    }

    public function getMemberRunningCommissionId(): int
    {
        return (int) $this->getDetail('commission.running_commission_id');
    }

    public function hasMemberRunningCommissionId(): bool
    {
        return $this->hasDetail('commission.running_commission_id');
    }

    public function setMemberRunningCommissionId(int $memberRunningCommissionid): void
    {
        $this->setDetail('commission.running_commission_id', $memberRunningCommissionid);
    }

    public function computeCommission(CurrencyRate $convertionRate, string $productCommissionPercentage): void
    {
        $this->setCommissionConvertionRate($convertionRate);
        $this->setProductCommissionPercentage($productCommissionPercentage);

        $turnover = $this->getFirstSubTransaction()->getDwlTurnover();
        $commissionPercentageAsDecimal = Number::div($productCommissionPercentage, 100);

        $computed = [];
        $commission = new Money(
            $convertionRate->getSourceCurrency(),
            Number::mul($turnover, $commissionPercentageAsDecimal)->toString()
        );
        $computed[$commission->getCurrencyCode()] = $commission->getAmount();
        $computed['original'] = $commission->getAmount();

        if (!$convertionRate->getSourceCurrency()->isEqualTo($convertionRate->getDestinationCurrency())) {
            $convertedCommission = $commission->convertToCurrency(
                $convertionRate->getDestinationCurrency(),
                $convertionRate->getSourceRate(),
                $convertionRate->getDestinationRate()
            );
            $computed[$convertedCommission->getCurrencyCode()] = $convertedCommission->getAmount();
            $computed['original'] = $convertedCommission->getAmount();
        }

        $this->setDetail(self::DETAIL_COMMISSION_COMPUTED, $computed);
    }

    public function getCommissionForCurrency(string $code): string
    {
        return $this->getDetail(self::DETAIL_COMMISSION_COMPUTED . '.' . $code);
    }

    public function getComputedAmount(): array
    {
        return $this->getDetail(self::DETAIL_COMMISSION_COMPUTED, []);
    }

    public function setCommissionConvertions(array $convertions): void
    {
        $this->setDetail(self::DETAIL_COMMISSION_CONVERTIONS, $convertions);
    }

    public function getCommissionConvertions(): array
    {
        return $this->getDetail(self::DETAIL_COMMISSION_CONVERTIONS, []);
    }

    public function setFilename(string $filename): void
    {
        $this->setDetail(self::DETAIL_FILE_NAME, $filename);
    }

    public function getFilename(): string
    {
        $filename = $this->getDetail(self::DETAIL_FILE_NAME);

        return !empty($filename) ? $filename : '';
    }

    public function setFileFolder(string $folderName): void
    {
        $this->setDetail(self::DETAIL_FILE_FOLDER, $folderName);
    }

    public function getFileFolder(): string
    {
        return $this->getDetail(self::DETAIL_FILE_FOLDER);
    }

    public function hasFile(): bool
    {
        return !empty($this->getFilename()) ? true : false;
    }

    public function isFileViewable(): bool
    {
        if ($this->getFinishedAt()) {
            $currentDate = new DateTime('now');
            $interval = $this->getFinishedAt()->diff($currentDate);

            return $interval->format('%d') > self::FILE_DAY_LIMIT ? false : true;
        }

        return true;
    }

    public function onFinish(): void
    {
        if (($this->isDeclined() || $this->isEnd()) && !$this->isVoided()) {
            $this->setFinishedAt(new DateTime('now'));
        }
    }

    public function setBitcoinInfo(array $info): void
    {
        foreach ($info as $key => $value) {
            $this->setDetail($key, $value);
        }
    }

    public function getBitcoinInfo(): array
    {
        return $this->getDetail(self::DETAIL_BITCOIN_INFO);
    }

    public function getBitcoinAddress(): ?string
    {
        return $this->getDetail(self::DETAIL_BITCOIN_ADDRESS, '');
    }

    public function setBitcoinAddress(string $address): self
    {
        $this->setDetail(self::DETAIL_BITCOIN_ADDRESS, $address);

        return $this;
    }

    public function setBitcoinRateExpired(): self
    {
        $this->setDetail(self::DETAIL_BITCOIN_RATE_EXPIRED, true);

        return $this;
    }

    public function setBitcoinRateExpiration(bool $isExpired): self
    {
        $this->setDetail(self::DETAIL_BITCOIN_RATE_EXPIRED, $isExpired);

        return $this;
    }

    public function getBitcoinRate(): string
    {
        // zimi
        $res = $this->getDetail(self::DETAIL_BITCOIN_RATE, '');
        return ($res === null ? 0 : $res);
    }

    public function getBitcoinTransactionHash(): string
    {
        return $this->getDetail(self::DETAIL_BITCOIN_TRANSACTION_HASH, '');
    }

    public function setBitcoinTransactionHash(string $hash): self
    {
        $this->setDetail(self::DETAIL_BITCOIN_TRANSACTION_HASH, $hash);

        return $this;
    }

    public function setBitcoinRate(string $rate): self
    {
        $this->setDetail(self::DETAIL_BITCOIN_RATE, $rate);

        return $this;
    }

    public function getBitcoinCallback(): string
    {
        return $this->getDetail(self::DETAIL_BITCOIN_CALLBACK, '');
    }

    public function setBitcoinCallback(string $callback): self
    {
        $this->setDetail(self::DETAIL_BITCOIN_CALLBACK, $callback);

        return $this;
    }

    public function setBitcoinAcknowledgedByUser(bool $acknowledgedByUser): self
    {
        $this->setDetail(self::DETAIL_BITCOIN_ACKNOWLEDGED_BY_USER, $acknowledgedByUser);

        return $this;
    }

    public function getBitcoinAcknowledgeByUser(): bool
    {
        return $this->getDetail(self::DETAIL_BITCOIN_ACKNOWLEDGED_BY_USER);
    }

    public function getBitcoinIndex(): int
    {
        return (int) $this->getDetail(self::DETAIL_BITCOIN_BLOCKCHAIN_INDEX, 0);
    }

    public function setBitcoinIndex(int $index): self
    {
        $this->setDetail(self::DETAIL_BITCOIN_BLOCKCHAIN_INDEX, $index);

        return $this;
    }

    public function setBitcoinValue(string $value): self
    {
        $this->setDetail(self::DETAIL_BITCOIN_TRANSACTION_VALUE, $value);

        return $this;
    }

    public function getBitcoinValue(): string
    {
        return $this->getDetail(self::DETAIL_BITCOIN_TRANSACTION_VALUE, '');
    }

    public function setBitcoinValueInSatoshi(string $value): self
    {
        $this->setDetail(self::DETAIL_BITCOIN_TRANSACTION_VALUE_SATOSHI, $value);

        return $this;
    }

    public function getBitcoinValueInSatoshi(): string
    {
        return $this->getDetail(self::DETAIL_BITCOIN_TRANSACTION_VALUE_SATOSHI, '');
    }

    public function setBitcoinConfirmation(?int $confirmation): self
    {
        $this->setDetail(self::DETAIL_BITCOIN_CONFIRMATION, $confirmation);
        $this->bitcoinConfirmation = $confirmation;

        return $this;
    }

    public function setBitcoinConfirmationAsConfirmed(): self
    {
        $this->setBitcoinConfirmation(3);

        return $this;
    }

    public function getBitcoinConfirmation(): ?int
    {
        if ($this->bitcoinConfirmation === null) {
            $this->bitcoinConfirmation = $this->getDetail(self::DETAIL_BITCOIN_CONFIRMATION, null);
        }

        return $this->bitcoinConfirmation;
    }

    public function isBitcoinRequestedOnConfirmation(): bool
    {
        return $this->getBitcoinConfirmation() === null && $this->getStatus() === self::TRANSACTION_STATUS_START;
    }

    public function isBitcoinPendingOnConfirmation(): bool
    {
        $confirmations = $this->getBitcoinConfirmation();
        
        if ($confirmations === null) {
            return false;
        }

        return $confirmations < 3;
    }

    public function isBitcoinConfirmedOnConfirmation(): bool
    {
        return $this->getBitcoinConfirmation() >= 3
            || $this->getStatus() == self::TRANSACTION_STATUS_ACKNOWLEDGE;
    }

    public function getBitcoinStatus(): string
    {
        $status = self::DETAIL_BITCOIN_STATUS_NO_CONFIRMATION;

        if ($this->getBitcoinConfirmation() < 3){
            $status = self::DETAIL_BITCOIN_STATUS_PENDING;
        } else if ($this->getBitcoinConfirmation() >= 3) {
            $status = self::DETAIL_BITCOIN_STATUS_CONFIRMED;
        }

        return $status;
    }

    public function hasBitcoinDepositAndNotConfirmed(): bool
    {
        return $this->hasDepositUsingBitcoin() && $this->getBitcoinConfirmation() < 3 && $this->getStatus() != self::TRANSACTION_STATUS_ACKNOWLEDGE;
    }

    public static function getOtherStatus(): array
    {
        return [
            self::TRANSACTION_STATUS_VOIDED,
            self::DETAIL_BITCOIN_STATUS_CONFIRMED,
            self::DETAIL_BITCOIN_STATUS_PENDING,
        ];
    }

    public static function getPendingStatus(): array
    {
        return [
            self::DETAIL_BITCOIN_STATUS_PENDING, 
            self::TRANSACTION_STATUS_START,
            self::TRANSACTION_STATUS_ACKNOWLEDGE,
        ];
    }

    public function getBitcoinSenderAddresses(): array
    {
        return $this->getDetail(self::DETAIL_BITCOIN_TRANSACTION_SENDER_ADDRESS, []);
    }

    public function setBitcoinSenderAddresses(array $senderAddresses): self
    {
        $this->setDetail(self::DETAIL_BITCOIN_TRANSACTION_SENDER_ADDRESS, $senderAddresses);

        return $this;
    }

    private function setCommissionConvertionRate(CurrencyRate $rate): void
    {
        if (!$this->getCurrency()->isEqualTo($rate->getSourceCurrency())) {
            throw new Exception(sprintf(
                'The source currency must be equal to transaction currency, Source Currency %s given',
                $rate->getSourceCurrency()->getCode()
            ));
        }
        $this->setDetail(self::DETAIL_COMMISSION_CONVERTION_RATE, [
            'source' => [
                'currency' => $rate->getSourceCurrency()->getCode(),
                'rate' => $rate->getSourceRate(),
            ],
            'destination' => [
                'currency' => $rate->getDestinationCurrency()->getCode(),
                'rate' => $rate->getDestinationRate(),
            ],
        ]);
    }

    private function setProductCommissionPercentage(string $percentage): void
    {
        $this->setDetail(self::DETAIL_COMMISSION_PRODUCT_PERCENTAGE, $percentage);
    }

    public function getBitcoinTotalAmount(): string
    {
        if ($this->isPaymentBitcoin()) {
            $totalAmount = '0';

            foreach ($this->getSubTransactions() as $subTransaction) {
                $amount = $subTransaction->getDetail(SubTransaction::DETAIL_BITCOIN_REQUESTED_BTC, '0');
                $totalAmount = Number::add($totalAmount, $amount);
            }

            return $totalAmount;
        }

        return '';
    }

    // zimi
    public function getStatusText()
    {
        return $this->getStatusList()[$this->getStatus()];
    }

    /**
    const TRANSACTION_STATUS_START = 1;
    const TRANSACTION_STATUS_END = 2;
    const TRANSACTION_STATUS_DECLINE = 3;
    const TRANSACTION_STATUS_ACKNOWLEDGE = 4;
    const TRANSACTION_STATUS_VOIDED = 'voided';
    **/
    public function getStatusList(): array
    {
        return [
            static::TRANSACTION_STATUS_START => 'requested',
            static::TRANSACTION_STATUS_END => 'processed',
            static::TRANSACTION_STATUS_DECLINE => 'declined',
            static::TRANSACTION_STATUS_ACKNOWLEDGE => 'acknowledged',
            static::TRANSACTION_STATUS_VOIDED => 'voided',            
        ];
    }

    public function setProduct($product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * Set email.
     *
     * @param string $email
     *
     * @return Transaction
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set product id.
     *
     * @param string $productID
     *
     * @return Transaction
     */
    public function setProductID($id)
    {
        $this->productID = $id;

        return $this;
    }

    /**
     * Get product.
     *
     * @return string
     */
    public function getProductID()
    {
        return $this->productID;
    }

    /**
     * Set customer id.
     *
     * @param string $customerID
     *
     * @return Transaction
     */
    public function setCustomerID($id)
    {
        $this->customerID = $id;

        return $this;
    }

    /**
     * Get product.
     *
     * @return string
     */
    public function getCustomerID()
    {
        return $this->customerID;
    }
}
