<?php
/**
 * Created by PhpStorm.
 * User: cydrick
 * Date: 3/22/19
 * Time: 5:16 PM
 */

namespace TwoFactorBundle\Provider\Message;

interface MessengerInterface
{
    public function sendCode(string $code, string $to, array $payload = []): void;
}