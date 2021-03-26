<?php

namespace PaymentBundle\Form;

use PaymentBundle\Model\Bitcoin\BitcoinConfirmation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BitcoinConfirmationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('confirmationLabel', Type\TextType::class);
        $builder->add('confirmationTransactionStatus', Type\ChoiceType::class, [
            'choices' => $options['transactionStatuses'] ?? [],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BitcoinConfirmation::class,
            'transactionStatuses' => [],
        ]);
    }
}
