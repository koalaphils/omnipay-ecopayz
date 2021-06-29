<?php

namespace PaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;
use DbBundle\Entity\BitcoinRateSetting;

class BitcoinRateSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {   
        $builder
            ->add('rangeFrom', Type\TextType::class)
            ->add('rangeTo', Type\TextType::class)
            ->add('fixedAdjustment', Type\TextType::class)
            ->add('percentageAdjustment', Type\TextType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {   
        $resolver->setDefaults([
            'data_class' => BitcoinRateSetting::class,
        ]);
    }
}
