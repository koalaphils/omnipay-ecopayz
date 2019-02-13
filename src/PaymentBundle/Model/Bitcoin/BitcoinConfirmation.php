<?php

namespace PaymentBundle\Model\Bitcoin;

class BitcoinConfirmation
{
    private $confirmationNumber;
    private $confirmationLabel;
    private $confirmationTransactionStatus;

    public static function create(int $num, string $label, string $status): self
    {
        $bitcoinConfirmation = new self();
        $bitcoinConfirmation->setConfirmationNumber($num);
        $bitcoinConfirmation->setConfirmationLabel($label);
        $bitcoinConfirmation->setConfirmationTransactionStatus($status);

        return $bitcoinConfirmation;
    }

    public function getConfirmationNumber(): ?int
    {
        return $this->confirmationNumber;
    }

    public function setConfirmationNumber(?int $confirmationNumber): self
    {
        $this->confirmationNumber = $confirmationNumber;

        return $this;
    }

    public function getConfirmationLabel(): ?string
    {
        return $this->confirmationLabel;
    }

    public function setConfirmationLabel(?string $confirmationLabel): self
    {
        $this->confirmationLabel = $confirmationLabel;

        return $this;
    }

    public function getConfirmationTransactionStatus(): ?string
    {
        return $this->confirmationTransactionStatus;
    }

    public function setConfirmationTransactionStatus(?string $confirmationTransactionStatus): self
    {
        $this->confirmationTransactionStatus = $confirmationTransactionStatus;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'num' => $this->getConfirmationNumber(),
            'label' => $this->getConfirmationLabel(),
            'transactionStatus' => $this->getConfirmationTransactionStatus(),
        ];
    }
}
