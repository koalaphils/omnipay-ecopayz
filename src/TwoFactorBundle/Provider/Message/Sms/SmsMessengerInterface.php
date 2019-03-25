<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message\Sms;

interface SmsMessengerInterface
{
    public function send(string $message, string $to, string $from = ''): void;
}