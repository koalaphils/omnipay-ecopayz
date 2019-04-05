<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction;

use ApiBundle\Request\Transaction\Meta\Meta;
use DbBundle\Entity\Customer;
use DbBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

class WithdrawRequest implements GroupSequenceProviderInterface
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

    /**
     * @var string
     */
    protected $verificationCode;

    /**
     * @var Customer
     */
    protected $member;

    public static function createFromRequest(Request $request): self
    {
        $instance = new static();
        $instance->paymentOptionType = $request->get('payment_option_type', '');
        $instance->meta = Meta::createFromArray($request->get('meta', []), $instance->paymentOptionType, false, false);
        $instance->paymentOption = $request->get('payment_option', '');
        $instance->products = [];
        $instance->verificationCode = $request->get('verification_code', '');
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

    public function getGroupSequence()
    {
        return [['WithdrawRequest', $this->getPaymentOptionType()], 'afterBlank'];
    }

    public function getMemberId(): int
    {
        return $this->member->getId();
    }

    public function setMember(Customer $member): void
    {
        if ($this->member !== null) {
            throw new \RuntimeException("Member already exists, you can't change it anymore");
        }

        $this->member = $member;
    }

    public function getVerificationCode(): string
    {
        return $this->verificationCode;
    }

    public function getVerificationPayload(): array
    {
        $payload = ['purpose' => 'transaction'];
        if ($this->member->getUser()->getSignupType() === User::SIGNUP_TYPE_PHONE) {
            $payload['provider'] = 'sms';
            $payload['phone'] = $this->member->getUser()->getPhoneNumber();
        } else {
            $payload['provider'] = 'email';
            $payload['email'] = $this->member->getUser()->getEmail();
        }

        return $payload;
    }
}