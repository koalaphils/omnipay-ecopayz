<?php

namespace DbBundle\Entity;

use AppBundle\Service\PaymentOptionService;
use AppBundle\ValueObject\Number;
use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;

/**
 * Gateway.
 */
class Gateway extends Entity implements ActionInterface, TimestampInterface, AuditInterface, AuditAssociationInterface, \Payum\Core\Model\GatewayConfigInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    const PAYMENT_OPTION_BANKWIRE = 1;
    const PAYMENT_OPTION_NETELLER = 2;
    const PAYMENT_OPTION_SKRILL = 3;
    const PAYMENT_OPTION_PAYPAL = 4;
    const PAYMENT_OPTION_ECOPAYZ = 5;
    const PAYMENT_OPTION_BITCOIN = 6;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var string
     */
    private $balance;

    private $previousBalance;

    /**
     * @var string
     */
    private $paymentOption;

    /**
     * @var bool
     */
    private $isActive;

    /**
     * @var array
     */
    private $details;

    /**
     * @var array
     */
    private $levels;

    private $groups;

    private $paymentOptionEntity;

    public function __construct()
    {
        $this->previousBalance = 0;
        $this->details = [];
        $this->levels = [];
        $this->gateways = [];
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Gateway
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
     * @param string $currency
     *
     * @return Gateway
     */
    public function setCurrency(Currency $currency)
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
     * Set balance.
     *
     * @param string $balance
     *
     * @return Gateway
     */
    public function setBalance($balance)
    {
        $this->setPreviousBalance($this->balance);

        $this->balance = $balance;

        return $this;
    }

    /**
     * Get balance.
     *
     * @return string
     */
    public function getBalance()
    {
        return $this->balance;
    }

    public function setPreviousBalance($previousBalance)
    {
        $this->previousBalance = $previousBalance;

        return $this;
    }

    /**
     * Get balance.
     *
     * @return string
     */
    public function getPreviousBalance()
    {
        return $this->previousBalance;
    }

    /**
     * Set paymentOption.
     *
     * @param string $paymentOption
     *
     * @return Gateway
     */
    public function setPaymentOption($paymentOption)
    {
        $this->paymentOption = $paymentOption;

        return $this;
    }

    /**
     * Get paymentOption.
     *
     * @return string
     */
    public function getPaymentOption()
    {
        return $this->paymentOption;
    }

    /**
     * Set isActive.
     *
     * @param bool $isActive
     *
     * @return Gateway
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive.
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->isActive;
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
     * Set details.
     *
     * @param array $details
     *
     * @return Gateway
     */
    public function setDetails($details = [])
    {
        $this->details = $details;

        return $this;
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
     * Get levels.
     *
     * @return array
     */
    public function getLevels()
    {
        return $this->levels;
    }

    /**
     * Set levels.
     *
     * @param array $levels
     *
     * @return Gateway
     */
    public function setLevels($levels = [])
    {
        $this->levels = [];
        foreach ($levels as $level) {
            $this->levels[] = $level;
        }

        return $this;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function setGroups($groups)
    {
        $this->groups = $groups;

        return $this;
    }

    public function addGroup(CustomerGroupGateway $group)
    {
        $this->groups[] = $group;

        return $this;
    }

    public function getPaymentOptionEntity(): ?PaymentOption
    {
        return $this->paymentOptionEntity;
    }

    public function setPaymentOptionEntity($paymentOptionEntity)
    {
        $this->paymentOptionEntity = $paymentOptionEntity;

        return $this;
    }

    public function add($amount)
    {
        $currentBalance = new Number($this->getBalance());

        $this->setBalance($currentBalance->plus($amount));
    }

    public function sub($amount)
    {
        $currentBalance = new Number($this->getBalance());

        $this->setBalance($currentBalance->minus($amount));
    }

    public function suspend()
    {
        $this->setIsActive(false);

        return $this;
    }

    public function enable()
    {
        $this->setIsActive(true);

        return $this;
    }

    public function getConfig(): array
    {
        return $this->getDetail('config', []);
    }

    public function getFactoryName($default = ''): string
    {
        return '';
    }

    public function getGatewayName(): string
    {
        return PaymentOptionService::getPaymentMode($this->paymentOption);
    }

    public function setConfig(array $config)
    {
        $this->setDetail('config', $config);
    }

    public function setFactoryName($name)
    {
        return $this;
    }

    public function setGatewayName($gatewayName)
    {
        return $this;
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_GATEWAY;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt', 'paymentOption'];
    }

    public function getAssociationFields()
    {
        return ['currency', 'paymentOptionEntity'];
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
        return ['name' => $this->getName(), 'balance' => $this->getBalance(), 'paymentOptionEntity' => $this->getPaymentOptionEntity()];
    }

    public function getFinalAmount(): float
    {
        $previousBalance = new Number($this->getPreviousBalance());

        return $previousBalance->minus($this->getBalance())->toFloat();
    }

    public function getNameAndCurrencyCode()
    {
        return $this->getName() . ' (' . $this->getCurrency()->getCode() . ')';
    }

    public function getMethodForTransactionType(string $type): array
    {
        if ($type === 'bonus') {
            return [
                'equation' => '-x',
                'varialbles' => [
                    'total_amount' => 'x'
                ]
            ];
        }

        return $this->getDetail('methods.' . $type);
    }

    public function getMethodEquationForTransactionType(string $type): string
    {
        $method = $this->getMethodForTransactionType($type);

        return $method['equation'];
    }

    public function getMethodVariablesForTransactoinType(string $type): array
    {
        $method = $this->getMethodForTransactionType($type);

        return $method['variables'] ?? [];
    }

    public function getMethods(): array
    {
        return $this->getDetail('methods');
    }
}
