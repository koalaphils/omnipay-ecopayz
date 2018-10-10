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
class SecurityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                $builder
                    ->create('user', Type\FormType::class, [
                        'data_class' => \DbBundle\Entity\User::class,
                        'constraints' => [new \Symfony\Component\Validator\Constraints\Valid()],
                    ])
                    ->add('password', Type\RepeatedType::class, [
                        'type' => Type\PasswordType::class,
                        'required' => false,
                        'invalid_message' => 'user.password.mismatch',
                        'first_options' => ['label' => 'fields.newPassword', 'translation_domain' => 'CustomerBundle'],
                        'second_options' => ['label' => 'fields.confirmNewPassword', 'translation_domain' => 'CustomerBundle'],
                        'mapped' => $builder->getOption('password_mapped', true),
                    ])
            )
            ->add('transactionPassword', Type\RepeatedType::class, [
                'type' => Type\PasswordType::class,
                'required' => false,
                'invalid_message' => 'user.password.mismatch',
                'first_options' => ['label' => 'fields.newTransactionPassword', 'translation_domain' => 'CustomerBundle'],
                'second_options' => ['label' => 'fields.confirmNewTransactionPassword', 'translation_domain' => 'CustomerBundle'],
                'mapped' => $builder->getOption('transaction_mapped', true),
            ])
            ->add('savePassword', Type\SubmitType::class, [
                'label' => 'form.save',
                'translation_domain' => 'AppBundle',
                'attr' => [ 'class' => 'btn-success pull-right'],
            ])
            ->add('saveTransactionPassword', Type\SubmitType::class, [
                'label' => 'form.save',
                'translation_domain' => 'AppBundle',
                'attr' => [ 'class' => 'btn-success pull-right'],
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
            'csrf_token_id' => 'customerSecurity',
            'validation_groups' => 'default',
            'cascade_validation' => true,
            'password_mapped' => true,
            'transaction_mapped' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'CustomerSecurity';
    }
}
