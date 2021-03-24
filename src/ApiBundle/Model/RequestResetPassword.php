<?php

namespace ApiBundle\Model;

class RequestResetPassword
{
    private $email;

    public function setEmail($email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }
}
