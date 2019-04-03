<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction\Meta;

class Fields
{
    private $email;

    public static function createFromArray(array $data): self
    {
        $instance = new static();
        $instance->email = $data['email'] ?? '';

        return $instance;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function toArray(): array
    {
        return ['email' => $this->email];
    }
}