<?php

namespace PaymentBundle\Form;

use AppBundle\Manager\SettingManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Form\Type as CType;
use PaymentBundle\Model\Bitcoin\SettingModel;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Description of BitcoinSettingType.
 *
 * @author Paolo Abendanio <cesar.abendanio@zmtsys.com>
 */
class BitcoinSettingType extends AbstractType
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
            ->add('minutesInterval', Type\IntegerType::class, [
                'label' => 'settings.bitcoin.fields.minutesInterval',
                'required' => true,
                'translation_domain' => 'AppBundle',
            ])
            ->add('status', Type\ChoiceType::class, [
                'label' => 'settings.scheduler.fields.status',
                'required' => true,
                'translation_domain' => 'AppBundle',
                'choices' => $statusChoices,
            ])
            ->add('minimumAllowedDeposit', Type\TextType::class, [
                'label' => 'settings.bitcoin.fields.minimumDeposit',
                'required' => true,
                'translation_domain' => 'AppBundle',
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ])
            ->add('maximumAllowedDeposit', Type\TextType::class, [
                'label' => 'settings.bitcoin.fields.maximumDeposit',
                'required' => true,
                'translation_domain' => 'AppBundle',
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ])
            ->add('autoLock', CType\SwitchType::class, [
                'label' => 'settings.bitcoin.fields.autoLock',
                'required' => false,
                'translation_domain' => 'AppBundle',
            ])
            ->add('minutesLockDownInterval', Type\IntegerType::class, [
                'label' => 'settings.bitcoin.fields.minutesLockDownInterval',
                'required' => true,
                'translation_domain' => 'AppBundle',
            ])
            ->add('minimumAllowedWithdrawal', Type\TextType::class, [
                'label' => 'settings.bitcoin.fields.minimumWithdrawal',
                'required' => true,
                'translation_domain' => 'AppBundle',
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ])
            ->add('maximumAllowedWithdrawal', Type\TextType::class, [
                'label' => 'settings.bitcoin.fields.maximumWithdrawal',
                'required' => true,
                'translation_domain' => 'AppBundle',
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ])
            ->add('saveConfigurationButton', Type\SubmitType::class, [
                'label' => 'form.save',
                'translation_domain' => 'AppBundle',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SettingModel::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'setting_bitcoin',
            'validation_groups' => 'bitcoinSetting',
            'constraints' => [new Valid()],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'BitcoinSetting';
    }
}
