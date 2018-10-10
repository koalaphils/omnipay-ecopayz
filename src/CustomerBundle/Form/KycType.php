<?php

namespace CustomerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Form\Type as CType;
use DbBundle\Entity\Customer;
/**
 * Description of KycType
 *
 * @author paolo<cesar.abendanio@zmtsys.com>
 */
class KycType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $label = ($options['guestType'] == Customer::AFFILIATE) ? Customer::AFFILIATE : Customer::CUSTOMER;
        $builder
            ->add('verify', CType\SwitchType::class, [
                'label' => 'fields.verify' . $label,
                'required' => false,
                'translation_domain' => 'CustomerBundle',
            ])
            ->add('save', Type\ButtonType::class, [
                'label' => 'form.save',
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
            'data_class' => 'DbBundle\Entity\Customer',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'customerKyc',
            'validation_groups' => 'default',
            'guestType' => null,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'CustomerKyc';
    }
}