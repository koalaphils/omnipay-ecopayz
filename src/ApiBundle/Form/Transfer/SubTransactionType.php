<?php

namespace ApiBundle\Form\Transfer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ApiBundle\Form\DataTransformer\CustomerProductTransformer;
use ApiBundle\Model\SubTransaction;

class SubTransactionType extends AbstractType
{
    private $customerProductTransformer;

    public function __construct(CustomerProductTransformer $customerProductTransformer)
    {
        $this->customerProductTransformer = $customerProductTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['isP2P']) {
            $builder->add('code', Type\TextType::class);
            $builder->add('username', Type\TextType::class);
        } else {
            $builder->add('product', Type\IntegerType::class);
        }
        $builder->add('amount', Type\NumberType::class);

        if ($options['isP2P']) {
            $builder->addModelTransformer(new CallbackTransformer(
                function ($data) {
                    return $data;
                },
                function ($data) {
                    $customerProduct = $this->customerProductTransformer->reverseTransform(
                        ['code' => $data->getCode(), 'username' => $data->getUsername()]
                    );
                    $data->setProduct($customerProduct);    

                    return $data;
                }
            ));
        } else {
            $builder->get('product')->addModelTransformer($this->customerProductTransformer);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => \ApiBundle\Model\SubTransaction::class,
            'csrf_protection' => false,
            'isP2P' => false,
        ]);
    }
}
