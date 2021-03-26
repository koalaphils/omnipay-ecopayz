<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Form\Type as CType;

/**
 * Description of MaintenanceType.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class MaintenanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('enabled', CType\SwitchType::class, [
                'label' => 'settings.maintenance.fields.enabled',
                'required' => false,
                'translation_domain' => 'AppBundle',
            ])
            ->add('save', Type\SubmitType::class, [
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
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'setting_maintenance',
            'validation_groups' => 'default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'MaintenanceSetting';
    }
}
