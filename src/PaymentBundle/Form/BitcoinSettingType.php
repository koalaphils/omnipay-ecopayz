<?php

namespace PaymentBundle\Form;

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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('autoDecline', CType\SwitchType::class, [
                'label' => 'settings.bitcoin.fields.autoDecline',
                'required' => false,
                'translation_domain' => 'AppBundle',
            ])
            ->add('minutesInterval', Type\IntegerType::class, [
                'label' => 'settings.bitcoin.fields.minutesInterval',
                'required' => true,
                'translation_domain' => 'AppBundle',
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
