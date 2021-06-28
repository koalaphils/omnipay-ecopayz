<?php

declare(strict_types = 1);

namespace PaymentOptionBundle\Event;

use DbBundle\Entity\PaymentOption;
use Symfony\Component\EventDispatcher\Event;

class PaymentOptionExtendEvent extends Event
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @var PaymentOption
     */
    private $paymentOption;

    /**
     * @var string
     */
    private $view;

    public function __construct(PaymentOption $paymentOption, string $view, array $parameters)
    {
        $this->paymentOption = $paymentOption;
        $this->view = $view;
        $this->parameters = $parameters;
    }

    public function getPaymentOption(): PaymentOption
    {
        return $this->paymentOption;
    }

    public function getView(): string
    {
        return $this->view;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setView(string $view): self
    {
        $this->view = $view;
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function setParameter(string $key, $value): self
    {
        array_set($this->parameters, $key, $value);

        return $this;
    }

    public function removeParameter(string $key): self
    {
        if (array_has($this->parameters, $key)) {
            array_forget($this->parameters, [$key]);
        }

        return $this;
    }
}