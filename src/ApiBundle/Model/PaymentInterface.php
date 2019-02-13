<?php

namespace ApiBundle\Model;

interface PaymentInterface
{
    public function getDetails(): array;
    public function toArray(): array;
    public function setTransaction(?Transaction $transaction): void;
    public function getTransaction(): ?Transaction;
}
