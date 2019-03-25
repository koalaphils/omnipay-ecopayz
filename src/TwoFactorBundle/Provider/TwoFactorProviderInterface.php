<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider;


interface TwoFactorProviderInterface
{
    public function validateAuthenticationCode(string $code, array $payload): bool;

    public function supports(string $code, array $payload): bool;
}