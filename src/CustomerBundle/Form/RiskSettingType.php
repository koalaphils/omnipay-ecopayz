<?php

namespace CustomerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Validator\Constraints\Valid;

use AppBundle\Form\Type as CType;

class RiskSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('riskId', Type\TextType::class)
            ->add('isActive', CType\SwitchType::class)
            ->add('productRiskSettings', Type\CollectionType::class, [
                'entry_type' => ProductRiskSettingType::class,
                'constraints' => [new Valid()],
                'allow_add' => true,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'data_class' => 'DbBundle\Entity\RiskSetting',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'riskSetting',
            'validation_groups' => 'Default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'riskSetting';
    }
}
