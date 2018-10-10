<?php

namespace ApiBundle\Form\Customer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SecurityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('hasChangePassword', Type\CheckboxType::class, [
            'required' => false,
            'data' => false,
        ]);
        $builder->add('password', Type\RepeatedType::class, [
            'type' => Type\PasswordType::class,
            'invalid_message' => 'The password fields must match.',
            'required' => false,
        ]);
        $builder->add('hasChangeUsername', Type\CheckboxType::class, [
            'required' => false,
            'data' => false,
        ]);
        $builder->add('username', Type\RepeatedType::class, [
            'type' => Type\TextType::class,
            'invalid_message' => 'The username fields must match.',
            'required' => false,
        ]);
        $builder->add('hasChangeTransactionPassword', Type\CheckboxType::class, [
            'required' => false,
            'data' => false,
        ]);
        $builder->add('transactionPassword', Type\RepeatedType::class, [
            'type' => Type\TextType::class,
            'invalid_message' => 'The transaction password fields must match.',
            'required' => false,
        ]);
        $builder->add('currentPassword', Type\PasswordType::class, [
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => \ApiBundle\Model\Security::class,
            'csrf_protection' => false,
            'validation_groups' => function (FormInterface $form) {
                $formData = $form->getData();
                $validationGroups = ['Default'];

                if ($formData->getHasChangePassword()) {
                    $validationGroups[] = 'password';
                }

                if ($formData->getHasChangeTransactionPassword()) {
                    $validationGroups[] = 'transactionPassword';
                }

                if ($formData->getHasChangeUsername()) {
                    $validationGroups[] = 'username';
                }

                return $validationGroups;
            }
        ]);
    }

    public function getBlockPrefix()
    {
        return 'security';
    }
}
