<?php

namespace CustomerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;

/**
 * Description of SecurityType.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class BankType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('details', Type\FormType::class);
        $builder->get('details')->add('bank', Type\FormType::class);
        $builder
            ->get('details')
                ->get('bank')
                    ->add('name', Type\TextType::class, [
                        'label' => 'fields.bank.name',
                        'required' => true,
                        'translation_domain' => 'GatewayBundle',
                    ])
                    ->add('holder', Type\TextType::class, [
                        'label' => 'fields.bank.holder',
                        'required' => true,
                        'translation_domain' => 'GatewayBundle',
                    ])
                    ->add('account', Type\TextType::class, [
                        'label' => 'fields.bank.account',
                        'required' => true,
                        'translation_domain' => 'GatewayBundle',
                    ]);

        $builder->add('save', Type\SubmitType::class, [
            'label' => 'form.save',
            'translation_domain' => 'AppBundle',
            'attr' => [
                'class' => 'btn-success',
            ],
        ]);
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
            'csrf_token_id' => 'customerBank',
            'validation_groups' => 'default',
            'cascade_validation' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'CustomerBank';
    }
}
