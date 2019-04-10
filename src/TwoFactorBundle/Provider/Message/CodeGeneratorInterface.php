<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message;

interface CodeGeneratorInterface
{
    public function generateCode(): string;
}