<?php

namespace ApiBundle\Form\Transaction\Extension;

use Symfony\Component\Form\FormBuilderInterface;

interface TransactionFormExtensionInterface
{
    public function extendTransactionForm(FormBuilderInterface $builder): void;
}
