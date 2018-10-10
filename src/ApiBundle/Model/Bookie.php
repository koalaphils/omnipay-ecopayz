<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace ApiBundle\Model;

/**
 * Description of Bookie
 *
 * @author paolo
 */
class Bookie
{
    private $code;
    private $username;

    public function __construct()
    {
        $this->code = '';
        $this->username = '';
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }
}