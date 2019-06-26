<?php

namespace AppBundle\Form;

use AppBundle\Manager\SettingManager;
use DbBundle\Entity\Transaction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Form\Type as CType;

class SchedulerType extends AbstractType
{
    /**
     * @var SettingManager
     */
    private $settingManager;

    public function __construct(SettingManager $settingManager)
    {
        $this->settingManager = $settingManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $statuses = $this->settingManager->getSetting('transaction.status');
        $statusChoices = [];
        foreach ($statuses as $key => $value) {
            $statusChoices[$value['label']] = $key;
        }

        $builder
            ->add('autoDecline', CType\SwitchType::class, [
                'label' => 'settings.scheduler.fields.enabled',
                'required' => false,
                'translation_domain' => 'AppBundle',
            ])
            ->add('minutesInterval', Type\IntegerType::class, [
                'label' => 'settings.scheduler.fields.minutesInterval',
                'required' => true,
                'translation_domain' => 'AppBundle',
            ])
            ->add('status', Type\ChoiceType::class, [
                'label' => 'settings.scheduler.fields.status',
                'required' => true,
                'translation_domain' => 'AppBundle',
                'choices' => $statusChoices,
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
            'csrf_token_id' => 'setting_scheduler',
            'validation_groups' => 'default',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'SchedulerSetting';
    }
}
