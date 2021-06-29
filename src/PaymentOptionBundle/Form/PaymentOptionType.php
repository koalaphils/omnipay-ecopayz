<?php

namespace PaymentOptionBundle\Form;

use AppBundle\Form\Type as CType;
use Payum\Core\Payum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Description of PaymentOption.
 *
 * @author cnonog
 */
class PaymentOptionType extends AbstractType
{
    private $payum;

    public function __construct(Payum $payum)
    {
        $this->payum = $payum;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', Type\TextType::class, [
                'label' => 'settings.paymentOption.fields.code',
                'translation_domain' => 'AppBundle',
                'empty_data' => '',
            ])
            ->add('name', Type\TextType::class, [
                'label' => 'settings.paymentOption.fields.label',
                'translation_domain' => 'AppBundle',
                'empty_data' => '',
            ])
            ->add('paymentMode', Type\ChoiceType::class, [
                'choices' => $this->getAllAvailablePaymentMode(),
            ])
            ->add('sort', Type\TextType::class, [
                'label' => 'settings.paymentOption.fields.sort',
                'translation_domain' => 'AppBundle',
                'empty_data' => '',
            ])
            ->add('isActive', CType\SwitchType::class, [
                'label' => 'settings.paymentOption.fields.isActive',
                'required' => false,
                'translation_domain' => 'AppBundle',
                'empty_data' => null,
            ])
            ->add('autoDecline', CType\SwitchType::class, [
                'label' => 'settings.paymentOption.fields.autoDecline',
                'required' => false,
                'translation_domain' => 'AppBundle',
                'empty_data' => null,
            ])
           ->add('fields', Type\CollectionType::class, [
                'entry_type' => PaymentOptionFieldType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'prototype_name' => '__fieldname__',
                'prototype_data' => [
                    'code' => '',
                    'label' => '',
                    'type' => 'text',
                ],
            ])
            ->add('image', CType\MediaType::class, [
                'label' => 'fields.image',
                'translation_domain' => 'BonusBundle',
                'required' => false,
            ])
        ;
        
        if (!is_null($options['id'])) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $paymentOption = $event->getData();
                $form = $event->getForm();
                if (!is_null($paymentOption)) {
                    $form->remove('code');
                }
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DbBundle\Entity\PaymentOption',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'paymentOption',
            'validation_groups' => 'Default',
            'id' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'paymentOption';
    }

    private function getAllAvailablePaymentMode(): array
    {
        $payum = $this->getPayum();

        $gateways = [];
        foreach ($payum->getGateways() as $key => $gateway) {
            $gateways[$key] = $key;
        }

        return $gateways;
    }

    private function getPayum(): Payum
    {
        return $this->payum;
    }
}
