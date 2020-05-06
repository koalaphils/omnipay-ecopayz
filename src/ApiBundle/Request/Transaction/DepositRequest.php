<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction;

use ApiBundle\Request\Transaction\Meta\Meta;
use DbBundle\Entity\PaymentOption;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

class DepositRequest implements GroupSequenceProviderInterface
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
     * @var Product[]
     */
    protected $products;

    /**
     * @var int
     */
    protected $memberId;

    public static function createFromRequest(Request $request): self
    {
        $instance = new static();
        $instance->paymentOptionType = $request->get('payment_option_type', '');
        $instance->meta = Meta::createFromArray($request->get('meta', []), $instance->paymentOptionType, false);
        $instance->paymentOption = $request->get('payment_option', '');
        $instance->products = [];
        foreach ($request->get('products', []) as $product) {
            $instance->products[] = new Product(
                $product['username'] ?? '',
                $product['product_code'] ?? '',
                (string) $product['amount'] ?? '',
                $product['meta'] ?? [],
                $instance->paymentOptionType
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

    public function getMemberId(): int
    {
        return $this->memberId;
    }

    public function setMemberId(int $memberId): void
    {
        if ($this->memberId !== null) {
            throw new \RuntimeException("Member Id already exists, you can't change it anymore");
        }

        $this->memberId = $memberId;
    }

    public function getGroupSequence()
    {
        return [['DepositRequest', $this->getPaymentOptionType()], 'afterBlank'];
    }

    public function getPaymentDetails(): array
    {
        return $this->getMetaData('payment_details', []);
    }

    public function getEmail(): string
    {
        return $this->getMetaData('field.email', '');
    }

    public function isBitcoin(string $paymentOption): bool
    {
        return strtoupper($paymentOption) === strtoupper(PaymentOption::PAYMENT_MODE_BITCOIN);
    }
}