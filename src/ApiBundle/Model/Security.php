<?php

namespace ApiBundle\Model;

class Security
{
    private $hasChangeUsername;
    private $username;
    private $hasChangePassword;
    private $password;
    private $hasChangeTransactionPassword;
    private $transactionPassword;
    private $currentPassword;

    public function setHasChangeUsername($hasChangeUsername): self
    {
        $this->hasChangeUsername = $hasChangeUsername;

        return $this;
    }

    public function getHasChangeUsername()
    {
        return $this->hasChangeUsername;
    }

    public function setUsername($username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setHasChangePassword($hasChangePassword): self
    {
        $this->hasChangePassword = $hasChangePassword;

        return $this;
    }

    public function getHasChangePassword()
    {
        return $this->hasChangePassword;
    }

    public function setPassword($password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setHasChangeTransactionPassword($hasChangeTransactionPassword): self
    {
        $this->hasChangeTransactionPassword = $hasChangeTransactionPassword;

        return $this;
    }

    public function getHasChangeTransactionPassword()
    {
        return $this->hasChangeTransactionPassword;
    }

    public function setTransactionPassword($transactionPassword): self
    {
        $this->transactionPassword = $transactionPassword;

        return $this;
    }

    public function getTransactionPassword()
    {
        return $this->transactionPassword;
    }

    public function setCurrentPassword($currentPassword): self
    {
        $this->currentPassword = $currentPassword;

        return $this;
    }

    public function getCurrentPassword()
    {
        return $this->currentPassword;
    }
}
