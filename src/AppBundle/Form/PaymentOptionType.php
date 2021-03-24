<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * Description of PaymentOption.
 *
 * @author cnonog
 */
class PaymentOptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', Type\TextType::class, [
                'label' => 'settings.paymentOption.fields.code',
                'translation_domain' => 'AppBundle',
            ])
            ->add('label', Type\TextType::class, [
                'label' => 'settings.paymentOption.fields.label',
                'translation_domain' => 'AppBundle',
            ])
            ->add('template', Type\TextareaType::class)
            ->add('fields', Type\CollectionType::class, [
                'entry_type' => PaymentOptionFieldType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'prototype_name' => '__fieldname__',
                'prototype_data' => [
                    'code' => '',
                    'label' => '',
                    'type' => Type\TextType::class,
                ],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => 'default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'paymentOption';
    }
}
