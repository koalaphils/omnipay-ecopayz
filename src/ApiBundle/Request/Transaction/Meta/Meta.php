<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction\Meta;

class Meta
{
    /**
     * @var PaymentInterface[]
     */
    private $paymentDetails;

    /**
     * @var Fields
     */
    private $fields;

    public static function createFromArray(array $data, string $paymentOptionType, bool $isProduct = false, bool $withPaymentDetails = true): self
    {
        $instance = new static();
        $instance->fields = Fields::createFromArray($data['field'] ?? []);
        $classPrefix = ucwords(strtolower($paymentOptionType));
        if ($isProduct) {
            $class = '\\ApiBundle\\Request\\Transaction\Meta\\' . $classPrefix . '\\' . $classPrefix . 'ProductPayment';
        } else {
            $class = '\\ApiBundle\\Request\\Transaction\Meta\\' . $classPrefix . '\\' . $classPrefix . 'Payment';
        }
        if ($withPaymentDetails && class_exists($class)) {
            $instance->paymentDetails[strtolower($paymentOptionType)] = $class::createFromArray($data['payment_details'][strtolower($paymentOptionType)] ?? []);
        } else {
            $instance->paymentDetails[strtolower($paymentOptionType)] = new DefaultPayment();
        }

        return $instance;
    }

    public function getFields(): Fields
    {
        return $this->fields;
    }

    public function getPaymentDetails(): array
    {
        return $this->paymentDetails;
    }

    public function getPaymentDetailsAsArray(): array
    {
        return array_map(function ($paymentDetails) {
            return $paymentDetails->toArray();
        }, $this->paymentDetails);
    }

    public function toArray(): array
    {
        return [
            'field' => $this->fields->toArray(),
            'payment_details' => array_map(function ($paymentDetails) {
                return $paymentDetails->toArray();
            }, $this->paymentDetails)
        ];
    }
}