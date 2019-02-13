<?php

namespace ApiBundle\Form\Customer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', Type\EmailType::class);
        $builder->add('firstName', Type\TextType::class);
        $builder->add('middleInitial', Type\TextType::class);
        $builder->add('lastName', Type\TextType::class);
        $builder->add('birthDate', Type\DateType::class, [
            'widget' => 'single_text',
            'format' => 'yyyy-MM-dd',
        ]);
        $builder->add('contact', Type\TextType::class);
        $builder->add('country', Type\TextType::class);
        $builder->add('socials', Type\CollectionType::class, [
            'allow_add' => true,
        ]);
        $builder->add('currency', Type\TextType::class);
        $builder->add('depositMethod', Type\TextType::class);
        $builder->add('bookies', Type\CollectionType::class, [
            'entry_type' => ProductType::class,
            'allow_add' => true,
            'constraints' => array(new \Symfony\Component\Validator\Constraints\Valid()),
        ]);
        $builder->add('banks', Type\CollectionType::class, [
            'allow_add' => true,
        ]);
        $builder->add('affiliate', Type\TextType::class);
        $builder->add('promo', Type\TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => \ApiBundle\Model\Register::class,
            'csrf_protection' => false,
            'constraints' => [new \Symfony\Component\Validator\Constraints\Valid(), ],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'register';
    }
}
