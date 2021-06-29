<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction\Meta;

use Symfony\Component\Validator\GroupSequenceProviderInterface;

class Fields implements GroupSequenceProviderInterface
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $accountId;

    /**
     * @var string
     */
    private $paymentOptionType;

    /**
     * @var bool
     */
    private $isDeposit;

    public static function createFromArray(array $data, string $paymentOptionType, bool $isDeposit = true): self
    {
        $instance = new static();
        $instance->email = $data['email'] ?? '';
        $instance->accountId = $data['account_id'] ?? '';
        $instance->paymentOptionType = $paymentOptionType;
        $instance->isDeposit = $isDeposit;

        return $instance;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getAccountId(): string
    {
        return $this->accountId;
    }

    public function toArray(): array
    {
        return ['email' => $this->email, 'account_id' => $this->accountId];
    }

    /**
     * Returns which validation groups should be used for a certain state
     * of the object.
     *
     * @return array An array of validation groups
     */
    public function getGroupSequence()
    {
        $groups = ['Fields'];
        if (!$this->isDeposit && $this->paymentOptionType === 'BITCOIN') {
            $groups[] = 'withAccountId';
        } else {
            $groups[] = 'withEmail';
        }

        return $groups;
    }
}