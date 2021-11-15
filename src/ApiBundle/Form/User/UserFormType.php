<?php

namespace ApiBundle\Form\User;

use DbBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Valid;


class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('email', Type\EmailType::class)
        ->add('password', Type\RepeatedType::class, array(
            'type' => Type\PasswordType::class,
            'invalid_message' => 'The password fields must match.',
        ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
            'validation_groups' => ['user_create'],
        ]);
    }
}
