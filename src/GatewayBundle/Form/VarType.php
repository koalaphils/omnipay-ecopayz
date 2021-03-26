<?php

namespace GatewayBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * Description of VarType.
 *
 * @author cnonog
 */
class VarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('var', Type\TextType::class, [
            'label' => 'fields.payment.vars.var',
            'translation_domain' => 'GatewayBundle',
        ])->add('value', Type\TextType::class, [
            'label' => 'fields.payment.vars.value',
            'translation_domain' => 'GatewayBundle',
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
        return 'var';
    }
}
