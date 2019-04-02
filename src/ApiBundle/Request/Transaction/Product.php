<?php

namespace ApiBundle\Request\Transaction;


class Product
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
     * @var array
     */
    private $meta;

    public function __construct(string $username, string $productCode, string $amount, array $meta)
    {
        $this->username = $username;
        $this->productCode = $productCode;
        $this->amount = $amount;
        $this->meta = $meta;
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

    public function getMeta(): array
    {
        return $this->meta;
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
}