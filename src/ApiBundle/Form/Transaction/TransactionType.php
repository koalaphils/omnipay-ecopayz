<?php

namespace ApiBundle\Form\Transaction;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ApiBundle\Form\DataTransformer\CustomerTransformer;

class TransactionType extends AbstractType
{
    private $customerTransformer;

    public function __construct(CustomerTransformer $customerTransformer)
    {
        $this->customerTransformer = $customerTransformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('subTransactions', Type\CollectionType::class, [
            'entry_type' => SubTransactionType::class,
            'allow_add' => true,
            'constraints' => [new \Symfony\Component\Validator\Constraints\Valid()],
            'entry_options' => ['hasFee' => $options['hasFee']],
        ]);

        $builder->add('customerFee', Type\NumberType::class);

        if ($options['hasTransactionPassword']) {
            $builder->add('transactionPassword', Type\TextType::class);
        }

        if ($options['hasEmail']) {
            $builder->add('email', Type\TextType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \ApiBundle\Model\Transaction::class,
            'csrf_protection' => false,
            'constraints' => [new \Symfony\Component\Validator\Constraints\Valid()],
            'hasFee' => false,
            'hasEmail' => false,
            'hasTransactionPassword' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'transaction';
    }
}