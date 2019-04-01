<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction;

use Symfony\Component\HttpFoundation\Request;

class DepositRequest
{
    /**
     * @var string
     */
    protected $paymentOptionType;

    /**
     * @var array
     */
    protected $meta;

    /**
     * @var string
     */
    protected $paymentOption;

    /**
     * @var array
     */
    protected $products;

    public static function createFromRequest(Request $request): self
    {
        $instance = new static();
        $instance->paymentOptionType = $request->get('payment_option_type', '');
        $instance->meta = $request->get('meta', []);
        $instance->paymentOption = $request->get('payment_option', '');
        $instance->products = $request->get('products', []);

        return $instance;
    }

    public function getPaymentOptionType(): string
    {
        return $this->paymentOptionType;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function getProducts(): array
    {
        return $this->products;
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

    public function getPaymentOption(): string
    {
        return $this->paymentOption;
    }
}