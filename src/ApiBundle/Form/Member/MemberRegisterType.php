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
        $builder->add('phoneNumber', Type\TextType::class);
        $builder->add('currency', Type\TextType::class);
        $builder->add('details', Type\CollectionType::class, [
            'allow_add' => true
        ]);
        
        //$builder->add('referralCode', Type\TextType::class);
        //$builder->add('referrerOriginSite', Type\TextType::class);
        //$builder->add('referreSite', Type\TextType::class);
        //$builder->add('registrationSite', Type\TextType::class);
        //$builder->add('registrationLocale', Type\TextType::class);
        //$builder->add('verificationCode', Type\TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
            'csrf_protection' => false,
            'constraints' => [new Valid()],
        ]);
    }
}
