<?php

namespace ApiBundle\Form\MemberProduct;

use Symfony\Component\Validator\Constraints;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ApiBundle\Request\CreateMemberProductRequest;

class MemberProductListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('memberProducts', Type\CollectionType::class, [
                'entry_type' => MemberProductType::class,
                'allow_add' => true,
                'constraints' => [new Constraints\Valid()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CreateMemberProductRequest\MemberProductList::class,
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'memberProductList';
    }
}