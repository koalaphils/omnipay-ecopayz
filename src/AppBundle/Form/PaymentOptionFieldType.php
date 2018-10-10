<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
