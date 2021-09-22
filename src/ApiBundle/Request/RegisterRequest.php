<?php

declare(strict_types = 1);

namespace ApiBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

class RegisterRequest implements GroupSequenceProviderInterface
{
    private $email;
    private $phoneNumber;
    private $countryPhoneCode;
    private $verificationCode;
    private $password;
    private $repeatPassword;
    private $currency;
    private $locale;
    private $ipAddress;
    private $referrerUrl;
    private $originUrl;
    private $registrationSite;
    private $referralCode;

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
        $instance->referralCode = $request->get('referral_code', '');

        $instance->locale = $request->getLocale();
        if ($request->request->has('registration_locale')) {
            $instance->locale = $request->get('registration_locale');
        }
        $instance->ipAddress = $request->getClientIp();
        $instance->referrerUrl = $request->get('referrer_site', '');
        $instance->originUrl = $request->get('referrer_origin_site', '');
        $instance->registrationSite = $request->get('registration_site', '');

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

    public function getRegistrationSite(): string
    {
        return $this->registrationSite;
    }

    public function getPhoneWithCountryCode(): string
    {
        return $this->getCountryPhoneCode() . $this->getPhoneNumber();
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
    
    public function getReferralCode()
    {
        return $this->referralCode;
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