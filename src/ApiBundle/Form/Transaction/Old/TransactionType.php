<?php

namespace ApiBundle\Form\Transaction;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ApiBundle\Form\DataTransformer\CustomerTransformer;
use Symfony\Component\Validator\Constraints\Valid;

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
            'constraints' => [new Valid()],
            'entry_options' => [
                'hasFee' => $options['hasFee'],
            ],
        ]);

        $builder->add('customerFee', Type\NumberType::class);
        $builder->add('bankDetails', Type\TextType::class, [
            'required' => false
        ]);

        if ($options['hasTransactionPassword']) {
            $builder->add('transactionPassword', Type\TextType::class);
        }

        if ($options['hasEmail']) {
            $builder->add('email', Type\TextType::class);
        }

        if ($options['hasAccountId']) {
            $builder->add('accountId', Type\TextType::class);
	}

        $builder->add('file', Type\FileType::class, [
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \ApiBundle\Model\Transaction::class,
            'csrf_protection' => false,
            'constraints' => [new Valid()],
            'hasFee' => false,
            'hasEmail' => false,
            'hasTransactionPassword' => false,
            'hasAccountId' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'transaction';
    }
}
