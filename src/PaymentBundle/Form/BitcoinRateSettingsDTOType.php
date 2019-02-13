<?php

namespace PaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use DbBundle\Entity\BitcoinRateSetting;
use PaymentBundle\Form\BitcoinRateSettingType;
use PaymentBundle\Model\Bitcoin\BitcoinRateSettingsDTO;

class BitcoinRateSettingsDTOType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {   
        $builder
            ->add('defaultRateSetting', BitcoinRateSettingType::class)
            ->add('bitcoinRateSettings', Type\CollectionType::class, [
                'entry_type' => BitcoinRateSettingType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'by_reference' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BitcoinRateSettingsDTO::class,
        ]);
    }
}
