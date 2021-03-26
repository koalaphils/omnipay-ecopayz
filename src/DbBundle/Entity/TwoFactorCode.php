<?php

declare(strict_types = 1);

namespace DbBundle\Entity;

use Ramsey\Uuid\UuidInterface;
use TwoFactorBundle\Provider\Message\CodeModel;

class TwoFactorCode extends CodeModel
{
    private $id;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setId(UuidInterface $id): self
    {
        $this->id = $id;

        return $this;
    }
}
