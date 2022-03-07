<?php

namespace ApiBundle\Form\Member;

use ApiBundle\Form\User\UserFormType;
use DbBundle\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Valid;

class MemberRegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('user', UserFormType::class);
        $builder->add('currency', Type\TextType::class, [
            'required' => true
        ]);
        $builder->add('details', Type\CollectionType::class, [
            'allow_add' => true
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
            'csrf_protection' => false,
            'constraints' => [new Valid()],
            'validation_groups' => ['Default']
        ]);
    }
}
