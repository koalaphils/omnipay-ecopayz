<?php

namespace ApiBundle\Form\Transfer;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ApiBundle\Form\DataTransformer\CustomerTransformer;
use ApiBundle\Form\DataTransformer\PaymentOptionTransformer;
use ApiBundle\Form\DataTransformer\CustomerProductTransformer;

class TransferType extends AbstractType
{
    private $customerTransformer;
    private $customerProductTransformer;

    public function __construct(
        CustomerTransformer $customerTransformer,
        CustomerProductTransformer $customerProductTransformer
    ) {
        $this->customerTransformer = $customerTransformer;
        $this->customerProductTransformer = $customerProductTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('to', Type\CollectionType::class, [
            'entry_type' => SubTransactionType::class,
            'allow_add' => true,
            'constraints' => [new \Symfony\Component\Validator\Constraints\Valid()],
            'entry_options' => ['isP2P' => $options['isP2P'], 'validation_groups' => $options['validation_groups']],
        ]);
        
        $builder->add('from', Type\IntegerType::class, [
            'invalid_message' => 'Customer product option does not exist',
        ]);

        if ($options['isP2P']) {
            $builder->add('transactionPassword', Type\TextType::class);
        }

        $builder->get('from')->addModelTransformer($this->customerProductTransformer);
        
        $builder->addModelTransformer(new CallbackTransformer(
            function ($data) {
                return $data;
            },
            function ($data) {
                foreach ($data->getTo() as $subtransaction) {
                    $subtransaction->setTransaction($data);
                }
                
                return $data;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \ApiBundle\Model\Transfer::class,
            'csrf_protection' => false,
            'constraints' => [new \Symfony\Component\Validator\Constraints\Valid()],
            'isP2P' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'transfer';
    }
}
