<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message\Email;

use TwoFactorBundle\Provider\TwoFactorProviderInterface;
use TwoFactorBundle\Provider\Message\Exceptions\CodeDoesNotExistsException;
use TwoFactorBundle\Provider\Message\StorageInterface;

class EmailProvider implements TwoFactorProviderInterface
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
            // $codeModel = $this->storage->getCode($otpCode, $payload['email']);
            $codeModel = $this->storage->getCode($code);
        } catch (CodeDoesNotExistsException $ex) {
            return false;
        }

        return
            $codeModel->getCode() === $code
            && !$codeModel->isExpired()
            && !$codeModel->isUsed()
            && $codeModel->getPayload('email') === ($payload['email'] ?? '')
            && $codeModel->getPayload('purpose') === ($payload['purpose'] ?? '')
        ;
    }

    public function supports(string $code, array $payload): bool
    {
        return $payload['provider'] === 'email';
    }
}