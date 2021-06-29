<?php

namespace PaymentBundle\Component\Blockchain\Wallet;

class Credentials
{
    private $guid;
    private $password;
    private $secondPassword;

    public static function create(string $guid, string $password, string $secondPassword = ''): self
    {
        $instance = new self();
        $instance->password = $password;
        $instance->secondPassword = $secondPassword;
        $instance->guid = $guid;

        return $instance;
    }

    public function getGuid(): string
    {
        return $this->guid;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getSecondPassword(): string
    {
        return $this->secondPassword;
    }

    public function setSecondPassword(string $secondPassword): self
    {
        $this->secondPassword = $secondPassword;

        return $this;
    }

    public function hasSecondPassword(): bool
    {
        return $this->secondPassword !== '';
    }

    private function __construct()
    {
    }
}
