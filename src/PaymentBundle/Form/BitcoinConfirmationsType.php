<?php

namespace PaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BitcoinConfirmationsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('confirmations', Type\CollectionType::class, [
            'entry_type' => BitcoinConfirmationType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'prototype_name' => '__fieldname__',
            'entry_options' => [
                'transactionStatuses' => $options['transactionStatuses'] ?? [],
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'transactionStatuses' => [],
        ]);
    }
}
