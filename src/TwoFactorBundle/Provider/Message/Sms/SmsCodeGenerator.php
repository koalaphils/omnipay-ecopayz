<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message\Sms;

use TwoFactorBundle\Provider\Message\CodeGeneratorInterface;

class SmsCodeGenerator implements CodeGeneratorInterface
{
    public function generateCode(): string
    {
        return generate_code(6, null, 'd');
    }
}