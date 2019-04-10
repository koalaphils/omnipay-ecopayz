<?php

declare(strict_types = 1);

namespace ApiBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

class ForgotPasswordRequest implements GroupSequenceProviderInterface
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

    public static function createFromRequest(Request $request): self
    {
        $instance = new static();
        $instance->email = $request->get('email', '');
        $instance->countryPhoneCode = $request->get('country_phone_code', '');
        $instance->phoneNumber = $request->get('phone_number', '');
        $instance->verificationCode = $request->get('verification_code', '');
        $instance->password = $request->get('password', '');
        $instance->repeatPassword = $request->get('repeat_password', '');

        return $instance;
    }

    public function getVerificationCode(): string
    {
        return $this->verificationCode;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getCountryPhoneCode(): string
    {
        return $this->countryPhoneCode;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getRepeatPassword(): string
    {
        return $this->repeatPassword;
    }

    public function getPhoneWithCountryCode(): string
    {
        return $this->countryPhoneCode . $this->phoneNumber;
    }

    public function getVerificationPayload(): array
    {
        $payload = ['purpose' => 'reset-password'];
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
            $groups[] = ['ForgotPasswordRequest', 'Phone'];
        } else {
            $groups[] = ['ForgotPasswordRequest', 'Email'];
        }

        return $groups;
    }
}