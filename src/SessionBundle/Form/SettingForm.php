<?php

declare(strict_types = 1);

namespace SessionBundle\Form;

use AppBundle\ValueObject\Number;
use SessionBundle\Model\SettingModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sessionTimeout', NumberType::class, [
                'label' => 'Session Timeout in Seconds'
            ])
            ->add('pinnacleTimeout', NumberType::class, [
                'label' => 'Pinnacle Timeout in Seconds'
            ])
            ->add('saveBtn', SubmitType::class, [
                'label' => 'Save'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        return [
            'data_class' => SettingModel::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'currency',
        ];
    }
}