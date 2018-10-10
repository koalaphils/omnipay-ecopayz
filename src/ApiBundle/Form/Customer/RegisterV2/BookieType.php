<?php

namespace ApiBundle\Form\Customer\RegisterV2;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;

class BookieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('product', EntityType::class, [
            'invalid_message' => 'Product code is invalid.',
            'class' => 'DbBundle:Product',
            'choice_value' => 'code',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('p')
                    ->select('p');
            },
        ]);
        $builder->add('username', Type\TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => \ApiBundle\Model\RegisterV2\Bookie::class,
            'csrf_protection' => false,
        ]);
    }
}