<?php

namespace CurrencyBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use DbBundle\Entity\Currency;
use AppBundle\Manager\SettingManager;
use Symfony\Component\Form\FormInterface;

class CurrencyType extends AbstractType
{
    private $settingManager;

    public function __construct(SettingManager $settingManager)
    {
        $this->settingManager = $settingManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('code', Type\TextType::class, [
            'label' => 'fields.code',
            'required' => true,
            'translation_domain' => 'CurrencyBundle',
        ])->add('name', Type\TextType::class, [
            'label' => 'fields.name',
            'required' => true,
            'translation_domain' => 'CurrencyBundle',
        ])->add('rate', Type\NumberType::class, [
            'label' => 'fields.rate',
            'required' => true,
            'translation_domain' => 'CurrencyBundle',
            'scale' => 10,
        ])->add('save', Type\SubmitType::class, [
            'label' => 'form.save',
            'translation_domain' => 'AppBundle',
        ]);

        if ($builder->getData() instanceof Currency
            && $builder->getData()->getId() === $this->settingManager->getSetting('currency.base', null)
        ) {
            $builder->remove('rate');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DbBundle\Entity\Currency',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'currency',
            'validation_groups' => function (FormInterface $form) {
                $validationGroups = ['default'];

                if ($form->has('rate')) {
                    $validationGroups[] = 'hasRate';
                }

                return $validationGroups;
            }
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'Currency';
    }
}
