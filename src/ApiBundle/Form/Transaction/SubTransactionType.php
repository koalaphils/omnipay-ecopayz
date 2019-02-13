<?php

namespace ApiBundle\Form\Transaction;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use DbBundle\Entity\Transaction;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ApiBundle\Form\DataTransformer\CustomerProductTransformer;

class SubTransactionType extends AbstractType
{
    private $customerProductTransformer;

    public function __construct(CustomerProductTransformer $customerProductTransformer)
    {
        $this->customerProductTransformer = $customerProductTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('product', Type\IntegerType::class);
        $builder->add('amount', Type\TextType::class);
        if ($options['hasFee']) {
            $builder->add('forFee', Type\ChoiceType::class, [
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
            ]);
        }

        $builder->get('product')->addModelTransformer($this->customerProductTransformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => \ApiBundle\Model\SubTransaction::class,
            'csrf_protection' => false,
            'hasFee' => false,
        ]);
    }
}
