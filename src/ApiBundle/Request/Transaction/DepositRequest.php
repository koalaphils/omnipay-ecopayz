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
     * @var string
     */
    protected $amount;

    /**
     * @var array
     */
    protected $meta;

    /**
     * @var string
     */
    protected $paymentOption;

    public static function createFromRequest(Request $request): self
    {
        $instance = new static();
        $instance->paymentOptionType = $request->get('payment_option_type', '');
        $instance->amount = (string) $request->get('amount', '');
        $instance->meta = $request->get('meta', []);
        $instance->paymentOption = $request->get('payment_option', '');

        return $instance;
    }

    public function getPaymentOptionType(): string
    {
        return $this->paymentOptionType;
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
    public function getMetaData(string $key, $default)
    {
        return array_get($this->meta, $key);
    }

    public function getPaymentOption(): string
    {
        return $this->paymentOption;
    }
}