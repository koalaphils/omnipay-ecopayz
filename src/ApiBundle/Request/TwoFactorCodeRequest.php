<?php
/**
 * Created by PhpStorm.
 * User: cydrick
 * Date: 3/22/19
 * Time: 4:34 PM
 */

namespace ApiBundle\Request;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

class TwoFactorCodeRequest implements GroupSequenceProviderInterface
{
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
    private $email;

    /**
     * @var string
     */
    private $purpose;

    public static function createFromRequest(Request $request): self
    {
        $instance = new static();
        $instance->phoneNumber = $request->get('phone_number', '');
        $instance->countryPhoneCode = $request->get('country_phone_code', '');
        $instance->email = $request->get('email', '');
        $instance->purpose = $request->get('purpose', '');

        return $instance;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getCountryPhoneCode(): string
    {
        return $this->countryPhoneCode;
    }

    public function getPhoneWithCountryCode(): string
    {
        return $this->countryPhoneCode . $this->phoneNumber;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function usePhone(): bool
    {
        return $this->email === '';
    }

    public function getGroupSequence()
    {
        if ($this->email === '') {
            return ['Phone'];
        }

        return ['Email'];
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    private function __construct()
    {
    }
}