<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction;

use ApiBundle\Request\Transaction\Meta\Meta;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

class Product implements GroupSequenceProviderInterface
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $productCode;

    /**
     * @var string
     */
    private $amount;

    /**
     * @var Meta
     */
    private $meta;

    /**
     * @var string
     */
    private $paymentOptionType;

    public function __construct(string $username, string $productCode, string $amount, array $meta, string $paymentOptionType, bool $metaWithPaymentDetails = true)
    {
        $this->username = $username;
        $this->productCode = $productCode;
        $this->amount = $amount;
        $this->meta = Meta::createFromArray($meta, $paymentOptionType, true, $metaWithPaymentDetails);
        $this->paymentOptionType = $paymentOptionType;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getProductCode(): string
    {
        return $this->productCode;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getMeta(): Meta
    {
        return $this->meta;
    }

    public function getPaymentOptionType(): string
    {
        return $this->paymentOptionType;
    }

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function getMetaData(string $key, $default = null)
    {
        return array_get($this->meta, $key, $default);
    }

    public function getGroupSequence()
    {
        return [['Product', $this->paymentOptionType], 'afterBlank'];
    }
}