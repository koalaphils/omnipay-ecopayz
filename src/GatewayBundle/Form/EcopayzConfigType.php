<?php

namespace GatewayBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;

class EcopayzConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('merchantId', Type\NumberType::class, []);
        $builder->add('merchantPassword', Type\TextType::class, []);
        $builder->add('merchantAccountNumber', Type\NumberType::class, []);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => 'default',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'gateway_config';
    }
}
