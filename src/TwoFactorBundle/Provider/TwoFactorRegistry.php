<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider;


class TwoFactorRegistry
{
    /**
     * @var TwoFactorProviderInterface[]
     */
    private $providers;

    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    public function addProvider(TwoFactorProviderInterface $provider, string $name): void
    {
        $this->providers[$name] = $provider;
    }

    public function getProvider(string $providerName): TwoFactorProviderInterface
    {
        return $this->providers[$providerName];
    }

    /**
     * @return TwoFactorProviderInterface[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    public function validateCode(string $code, array $payload): bool
    {
        $valid = false;
        foreach ($this->getProviders() as $provider) {
            if ($provider->supports($code, $payload)) {
                $valid = $provider->validateAuthenticationCode($code, $payload);
                if ($valid) {
                    break;
                }
            }
        }

        return $valid;
    }
}
