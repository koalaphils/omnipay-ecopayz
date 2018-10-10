<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace TransactionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * Description of MaintenanceType.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class TransactionSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $statuses = [];
        
        foreach ($builder->getData()['statuses'] as $statusKey => $statusInfo) {
            $statuses[$statusInfo['label']] = $statusKey;
        }
        
        $builder
            ->add('statuses', Type\CollectionType::class, [
                'entry_type' => StatusType::class,
                'allow_add' => true,
                'prototype' => true,
                'entry_options' => [
                    'statuses' => $builder->getData()['statuses'],
                ],
            ])
            ->add('paymentGateway', Type\ChoiceType::class, [
                'label' => 'settings.paymentGateway.label',
                'translation_domain' => 'TransactionBundle',
                'choices' => [
                    'settings.paymentGateway.choices.customerCurrency' => 'customer-currency',
                    'settings.paymentGateway.choices.customerGroup' => 'customer-group',
                ],
                'choices_as_values' => true,
                'required' => true,
            ])
            ->add('transactionAdminStart', Type\ChoiceType::class, [
                'label' => 'settings.transaction.admin.start_status',
                'choices' => $statuses,
                'property_path' => '[transactionStart][admin]',
            ])
            ->add('transactionCustomerStart', Type\ChoiceType::class, [
                'label' => 'settings.transaction.customer.start_status',
                'choices' => $statuses,
                'property_path' => '[transactionStart][customer]',
            ])
            ->add('save', Type\SubmitType::class, [
                'label' => 'form.save',
                'attr' => [
                    'class' => 'btn-success',
                ],
                'translation_domain' => 'AppBundle',
            ])
            ->add('cancel', Type\ButtonType::class, [
                'label' => 'form.cancel',
                'attr' => [
                    'class' => 'btn-info',
                ],
                'translation_domain' => 'AppBundle',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'setting_transaction',
            'validation_groups' => 'default',
            'translation_domain' => 'TransactionBundle',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'TransactionSetting';
    }
}
