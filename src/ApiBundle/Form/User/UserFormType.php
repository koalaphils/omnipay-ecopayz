<?php

namespace ApiBundle\Form\User;

use AppBundle\Validator\Constraints\Unique;
use DbBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Valid;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('email', Type\EmailType::class, [
            'required' => true,
            'constraints' => [
                new Unique([
                    'entityClass' => User::class,
                    'select' => " e.email ",
                    'expression' => " e.email = :value0 ",
                    'requiredKey' => ['email'],
                    'groups' => ['Email']
                ])
            ]
        ])
        ->add('password', Type\RepeatedType::class, [
            'required' => true,
            'type' => Type\PasswordType::class,
            'invalid_message' => 'Passwords do not match',
            'constraints' => [
                new Regex([
                    'pattern' => '/(?=^.{8,}$)(?=(.*[0-9]){2,})(?=(.*[A-Za-z]){2,})(?=(.*[+\-\/\{~\}!"^_`\[\]:$!@#%^&*\?]){2,})/',
                    'match' => true,
                    'message' => "Password is invalid. Must be at least 8 characters, contain at least 2 letters, 2 digits and 2 symbols."
                ])
            ]
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,
            'constraints' => [new Valid()],
            'validation_groups' => ['Default', 'Email']
        ]);
    }
}
