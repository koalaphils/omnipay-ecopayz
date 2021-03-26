<?php

namespace DbBundle\Entity;

use AppBundle\ValueObject\Number;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DateTimeInterface;
use DateTime;

class CustomerProduct extends Entity implements ActionInterface, TimestampInterface, AuditInterface, AuditAssociationInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    /**
     * @var int
     */
    protected $customerID;

    /**
     * @var string
     */
    protected $userName;

    /**
     * @var int
     */
    protected $productID;

    /**
     * @var decimal
     */
    protected $balance;

    /**
     * @var int
     */
    protected $isActive;

    /**
     * @var \DbBundle\Entity\Product
     */
    private $product;

    /**
     * @var int
     */
    private $currency;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var array
     */
    private $details;

    private $requestedAt;

    public static function create(Member $member): self
    {
        $memberProduct = new self();

        $memberProduct->setCustomer($member);

        return $memberProduct;
    }

    /**
     * Set customerID.
     *
     * @param int $customerID
     *
     * @return \DbBundle\Entity\CustomerProduct
     */
    public function setCustomerID($customerID)
    {
        $this->customerID = $customerID;

        return $this;
    }

    /**
     * Get customerID.
     *
     * @return int
     */
    public function getCustomerID()
    {
        return $this->customerID;
    }

    /**
     * Set customer.
     *
     * @param int $customer
     *
     * @return \DbBundle\Entity\CustomerProduct
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Get customer.
     *
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Set userName.
     *
     * @param string $userName
     *
     * @return \DbBundle\Entity\CustomerProduct
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * Get userName.
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * Set productID.
     *
     * @param int $productID
     *
     * @return \DbBundle\Entity\CustomerProduct
     */
    public function setProductID($productID)
    {
        $this->productID = $productID;

        return $this;
    }

    /**
     * Get productID.
     *
     * @return int
     */
    public function getProductID()
    {
        return $this->productID;
    }

    /**
     * Set balance.
     *
     * @param double $balance
     *
     * @return \DbBundle\Entity\CustomerProduct
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    public function addAmountToBalance(string $amount): self
    {
        $this->balance = Number::add((string) $this->balance, $amount)->toString();

        return $this;
    }

    /**
     * Get balance.
     *
     * @return decimal
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * Set isActive.
     *
     * @param bool $isActive
     *
     * @return \DbBundle\Entity\CustomerProduct
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function suspend()
    {
        $this->setIsActive(false);
        return $this;
    }

    public function activate()
    {
        $this->setIsActive(true);
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

    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function getProductName() : String
    {
        if ($this->product instanceof Product)
        {
            return $this->product->getName();
        }
        return '';
    }

    public function getDetails()
    {
        return $this->details;
    }

    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    public function setRequestedAt(DateTimeInterface $requestedAt): self
    {
        $this->requestedAt = $requestedAt;

        return $this;
    }

    public function getRequestedAt(): ?DateTimeInterface
    {
        return $this->requestedAt;
    }

    public function getDetail($key, $default = null)
    {
        return array_get($this->details, $key, $default);
    }

    public function setDetail($name, $value)
    {
        array_set($this->details, $name, $value);

        return $this;
    }

    public function getCurrencyId()
    {
        return $this->getCustomer()->getCurrency()->getId();
    }

    public function getCurrency()
    {
        return $this->getCustomer()->getCurrency();
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_CUSTOMER_PRODUCT;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt', 'customerID', 'productID', 'details'];
    }

    public function getAssociationFields()
    {
        return ['product', 'customer'];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getLabel()
    {
        return sprintf('%s (%s)', $this->getCustomer()->getFullName(), $this->getUserName());
    }

    public function isAudit()
    {
        return true;
    }

    public function getAssociationFieldName()
    {
        return $this->getUserName();
    }

    public function getAuditDetails(): array
    {
        return [
            'customer' => $this->getCustomer(),
            'product' => $this->getProduct(),
            'balance' => $this->getBalance(),
            'username' => $this->getUserName(),
        ];
    }

    /**
     * If the product is skype betting and customer
     * is not yet enabled, clear the balance value.
     */
    public function clearBalance()
    {
        if (!$this->hasBalance()) {
            $this->setBalance(0);
        }
    }

    public function revertBalanceFromSubtransaction(SubTransaction $subTransaction): void
    {
        $currentBalance = new Number($this->getBalance());
        $amount = $subTransaction->getConvertedAmount();

        if ($subTransaction->isDeposit()) {
            $revertedBalance = $currentBalance->minus($amount);
        } else {
            $revertedBalance = $currentBalance->plus($amount);
        }

        $this->setBalance($revertedBalance->toString());
    }

    public function getMember(): Customer
    {
        return $this->getCustomer();
    }

    public function isRequestedToday(): bool
    {
        if (!is_null($this->getRequestedAt())) {
            return (new DateTime('now'))->format('Y-m-d') == $this->getRequestedAt()->format('Y-m-d');
        }

        return false;
    }
}
