<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction;

use ApiBundle\Request\Transaction\Meta\Meta;
use Symfony\Component\HttpFoundation\Request;

class WithdrawRequest
{
    /**
     * @var string
     */
    protected $paymentOptionType;

    /**
     * @var Meta
     */
    protected $meta;

    /**
     * @var string
     */
    protected $paymentOption;

    /**
     * @var Product[]
     */
    protected $products;

    public static function createFromRequest(Request $request): self
    {
        $instance = new static();
        $instance->paymentOptionType = $request->get('payment_option_type', '');
        $instance->meta = Meta::createFromArray($request->get('meta', []), $instance->paymentOptionType, false, false);
        $instance->paymentOption = $request->get('payment_option', '');
        $instance->products = [];
        foreach ($request->get('products', []) as $product) {
            $instance->products[] = new Product(
                $product['username'] ?? '',
                $product['product_code'] ?? '',
                (string) ($product['amount'] ?? ''),
                $product['meta'] ?? [],
                $instance->paymentOptionType,
                false
            );
        }

        return $instance;
    }

    public function getPaymentOptionType(): string
    {
        return $this->paymentOptionType;
    }

    public function getMeta(): Meta
    {
        return $this->meta;
    }

    /**
     * @return Product[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    public function getPaymentOption(): string
    {
        return $this->paymentOption;
    }
}