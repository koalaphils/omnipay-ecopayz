<?php

namespace ApiBundle\Form\Customer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountActivationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('password', Type\RepeatedType::class, [
            'type' => Type\PasswordType::class,
            'invalid_message' => 'The password fields must match.',
            'required' => true,
        ]);
        $builder->add('username', Type\RepeatedType::class, [
            'type' => Type\TextType::class,
            'invalid_message' => 'The username fields must match.',
            'required' => true,
        ]);
        $builder->add('transactionPassword', Type\PasswordType::class, [
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => \ApiBundle\Model\AccountActivation::class,
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'activation';
    }
}
