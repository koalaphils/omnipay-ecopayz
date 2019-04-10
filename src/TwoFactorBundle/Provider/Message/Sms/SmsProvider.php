<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message\Sms;

use TwoFactorBundle\Provider\TwoFactorProviderInterface;
use TwoFactorBundle\Provider\Message\Exceptions\CodeDoesNotExistsException;
use TwoFactorBundle\Provider\Message\StorageInterface;

class SmsProvider implements TwoFactorProviderInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function validateAuthenticationCode(string $code, array $payload): bool
    {
        try {
            $codeModel = $this->storage->getCode($code);
        } catch (CodeDoesNotExistsException $ex) {
            return false;
        }

        return
            $codeModel->getCode() === $code
            && !$codeModel->isExpired()
            && !$codeModel->isUsed()
            && $codeModel->getPayload('phone') === ($payload['phone'] ?? '')
            && $codeModel->getPayload('purpose') === ($payload['purpose'] ?? '')
        ;
    }

    public function supports(string $code, array $payload): bool
    {
        return $payload['provider'] === 'sms';
    }
}