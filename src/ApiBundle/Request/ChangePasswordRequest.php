<?php

declare(strict_types = 1);

namespace ApiBundle\Request;

use DbBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

class ChangePasswordRequest implements GroupSequenceProviderInterface
{
    /**
     * @var string
     */
    private $verificationCode;

    /**
     * @var string
     */
    private $currentPassword;

    /**
     * @var string
     */
    private $newPasswaord;

    /**
     * @var string
     */
    private $repeatPassword;

    /**
     * @var User
     */
    private $user;

    public static function createFromRequestWithUser(Request $request, User $user)
    {
        $instance = new static();
        $instance->verificationCode = $request->get('verification_code', '');
        $instance->currentPassword = $request->get('current_password', '');
        $instance->newPasswaord = $request->get('password', '');
        $instance->repeatPassword = $request->get('repeat_password', '');
        $instance->user = $user;

        return $instance;
    }

    public function getVerificationCode(): string
    {
        return $this->verificationCode;
    }

    public function getCurrentPassword(): string
    {
        return $this->currentPassword;
    }

    public function getPassword(): string
    {
        return $this->newPasswaord;
    }

    public function getRepeatPassword(): string
    {
        return $this->repeatPassword;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getVerificationPayload(): array
    {
        $payload = ['purpose' => 'change-password'];
        if ($this->getUser()->getSignupType() === User::SIGNUP_TYPE_PHONE) {
            $payload['provider'] = 'sms';
            $payload['phone'] = $this->getUser()->getPhoneNumber();
        } else {
            $payload['provider'] = 'email';
            $payload['email'] = $this->getUser()->getEmail();
        }

        return $payload;
    }

    public function getGroupSequence()
    {
        return [
            'verification',
            'current',
            'new',
        ];
    }
}