<?php

namespace CustomerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use AppBundle\Form\Type as CType;
use CustomerBundle\Form\CustomerGroup\CustomerGroupGatewayType;

/**
 * CustomerGroupType
 */
class CustomerGroupType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', Type\TextType::class, [])
            ->add('isDefault', CType\SwitchType::class, [
                'label' => 'fields.isDefault',
                'required' => false,
                'translation_domain' => 'CustomerGroupBundle',
            ])
            ->add('gateways', Type\CollectionType::class, [
                'entry_type' => CustomerGroupGatewayType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
                'constraints' => new \Symfony\Component\Validator\Constraints\Valid(),
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            foreach ($event->getData()->getGateways() as &$gateway) {
                $gateway->setCustomerGroup($event->getData());
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DbBundle\Entity\CustomerGroup',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'customerGroup',
            'validation_groups' => 'Default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'CustomerGroup';
    }
}
