<?php

namespace ApiBundle\Form\Transaction\Extension;

use ApiBundle\Model\Bitcoin\BitcoinPayment;
use ApiBundle\Model\Bitcoin\BitcoinRateDetail;
use ApiBundle\Model\Bitcoin\BitcoinSubTransactionDetail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class BitcoinType extends AbstractType implements TransactionFormExtensionInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $rateDetailsForm = $builder
            ->create('rateDetails', FormType::class, [
                'data_class' => BitcoinRateDetail::class,
                'csrf_protection' => false,
                'constraints' => [new Valid()],
                'required' => true,
            ])
            ->add('rangeStart', TextType::class, ['required' => true])
            ->add('rangeEnd', TextType::class, ['required' => true])
            ->add('adjustment', TextType::class, ['required' => true])
            ->add('adjustmentType', ChoiceType::class, [
                'choices' => [
                    BitcoinRateDetail::ADJUSTMENT_TYPE_FIXED => BitcoinRateDetail::ADJUSTMENT_TYPE_FIXED,
                    BitcoinRateDetail::ADJUSTMENT_TYPE_PERCENTAGE => BitcoinRateDetail::ADJUSTMENT_TYPE_PERCENTAGE,
                ],
                'required' => true
            ])
        ;
        $builder
            ->add('blockchainRate', TextType::class, ['required' => true])
            ->add('rate', TextType::class, ['required' => true])
            ->add($rateDetailsForm)
        ;
    }
    
    public function extendTransactionForm(FormBuilderInterface $builder): void
    {
        $builder->get('subTransactions')->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();
            
            foreach ($data as $name => $value) {
                $form->get($name)->add('paymentDetails', FormType::class, [
                    'data_class' => BitcoinSubTransactionDetail::class,
                    'csrf_protection' => false,
                    'constraints' => [new Valid()],
                    'required' => true,
                ]);
                $form->get($name)->get('paymentDetails')->add('bitcoin', TextType::class);
            }
        });
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => BitcoinPayment::class,
            'csrf_protection' => false,
            'constraints' => [new Valid()],
        ]);
    }
}
