<?php

namespace PaymentOptionBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Form\Type as CType;

/**
 * Description of PaymentOptionField.
 *
 * @author cnonog
 */
class PaymentOptionFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code', Type\TextType::class, [
                'label' => 'settings.paymentOptionField.fields.code',
                'translation_domain' => 'AppBundle',
            ])
            ->add('label', Type\TextType::class, [
                'label' => 'settings.paymentOptionField.fields.label',
                'translation_domain' => 'AppBundle',
            ])
            ->add('type', Type\TextType::class, [
                'label' => 'settings.paymentOptionField.fields.type',
                'translation_domain' => 'AppBundle',
            ])
            ->add('isRequired', CType\SwitchType::class, [
                'label' => 'settings.paymentOptionField.fields.isRequired',
                'translation_domain' => 'AppBundle',
            ])
            ->add('isUnique', CType\SwitchType::class, [
                'label' => 'settings.paymentOptionField.fields.isUnique',
                'translation_domain' => 'AppBundle',
            ])
            ->add('order', Type\NumberType::class, [])
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
        return 'paymentOptionField';
    }
}
