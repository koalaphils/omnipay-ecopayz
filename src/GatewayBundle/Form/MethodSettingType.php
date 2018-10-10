<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace GatewayBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * Description of MethodSettingType.
 *
 * @author cnonog
 */
class MethodSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', Type\HiddenType::class, []);
        $builder->add('equation', Type\TextType::class, [
            'label' => 'fields.equation',
            'translation_domain' => 'GatewayBundle',
        ])->add('variables', Type\CollectionType::class, [
            'label' => 'fields.vars',
            'translation_domain' => 'GatewayBundle',
            'entry_type' => VarType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'prototype_name' => '__varname__',
            'required' => false,
        ]);
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
        return 'gateway_method';
    }
}
