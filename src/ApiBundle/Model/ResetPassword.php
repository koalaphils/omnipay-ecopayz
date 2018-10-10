<?php

namespace ApiBundle\Model;

class ResetPassword
{
    private $password;

    public function setPassword($password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }
}
