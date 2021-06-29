<?php

namespace ApiBundle\Model;

class AccountActivation
{
    private $password;
    private $username;
    private $transactionPassword;

    public function setUsername($username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername()
    {
        return $this->username;
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

    public function setTransactionPassword($transactionPassword): self
    {
        $this->transactionPassword = $transactionPassword;

        return $this;
    }

    public function getTransactionPassword()
    {
        return $this->transactionPassword;
    }
}
