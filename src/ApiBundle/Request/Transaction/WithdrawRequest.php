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
    protected $paymentOptionType;
    protected $meta;
    protected $paymentOption;
    protected $products;
    protected $verificationCode;
    protected $member;

    public static function createFromRequest(Request $request): self
    {
        $instance = new static();
        $instance->paymentOptionType = $request->get('payment_option_type', '');
        $instance->meta = Meta::createFromArray($request->get('meta', []), $instance->paymentOptionType, false, true, false);
        $instance->paymentOption = $request->get('payment_option', '');
        $instance->products = [];
        $instance->customerFee = $request->get('customer_fee', '');
        $instance->companyFee = $request->get('company_fee', '');
        $instance->verificationCode = $request->get('verification_code', '');
        foreach ($request->get('products', []) as $product) {
            $instance->products[] = new Product(
                $product['username'] ?? '',
                $product['product_code'] ?? '',
                (string) ($product['amount'] ?? ''),
                $product['meta'] ?? [],
                $instance->paymentOptionType,
                true
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
        return (int)$this->member->getId();
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
    
    public function setCustomerFee(string $customerFee)
    {
        $this->customerFee = $customerFee;
    }

    public function getCustomerFee()
    {
        return $this->customerFee;
    }

    public function setCompanyFee(string $companyFee)
    {
        $this->companyFee = $companyFee;
    }

    public function getCompanyFee()
    {
        return $this->companyFee;
    }

    public function getAccountId()
    {
        return $this->getMeta()->getFields()->getAccountId();
    }
}