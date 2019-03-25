<?php

declare(strict_types = 1);

namespace ApiBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

class RegisterRequest implements GroupSequenceProviderInterface
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $phoneNumber;

    /**
     * @var string
     */
    private $countryPhoneCode;

    /**
     * @var string
     */
    private $verificationCode;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $repeatPassword;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $ipAddress;

    /**
     * @var string
     */
    private $referrerUrl;

    /**
     * @var string
     */
    private $originUrl;

    public static function createFromRequest(Request $request): self
    {
        $instance = new static();
        $instance->email = $request->get('email', '');
        $instance->phoneNumber = $request->get('phone_number', '');
        $instance->verificationCode = $request->get('verification_code', '');
        $instance->password = $request->get('password', '');
        $instance->repeatPassword = $request->get('repeat_password', '');
        $instance->currency = $request->get('currency', '');
        $instance->countryPhoneCode = $request->get('country_phone_code', '');

        $instance->locale = $request->getLocale();
        $instance->ipAddress = $request->getClientIp();
        $instance->referrerUrl = $request->headers->get('Referrer', '');
        $instance->originUrl = $request->headers->get('Origin', '');

        return $instance;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getVerificationCode(): string
    {
        return $this->verificationCode;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRepeatPassword(): string
    {
        return $this->repeatPassword;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCountryPhoneCode(): string
    {
        return $this->countryPhoneCode;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getReferrerUrl(): string
    {
        return $this->referrerUrl;
    }

    public function getOriginUrl(): string
    {
        return $this->originUrl;
    }

    public function getPhoneWithCountryCode(): string
    {
        return str_replace('+','', $this->getCountryPhoneCode()) . $this->getPhoneNumber();
    }

    public function getVerificationPayload(): array
    {
        $payload = ['purpose' => 'register'];
        if ($this->email === '') {
            $payload['provider'] = 'sms';
            $payload['phone'] = $this->getPhoneWithCountryCode();
        } else {
            $payload['provider'] = 'email';
            $payload['email'] = $this->getEmail();
        }

        return $payload;
    }

    private function __construct()
    {
    }

    public function getGroupSequence()
    {
        $groups = [];
        if ($this->email === '') {
            $groups[] = ['RegisterRequest', 'Phone'];
        } else {
            $groups[] = ['RegisterRequest', 'Email'];
        }

        return $groups;
    }
}