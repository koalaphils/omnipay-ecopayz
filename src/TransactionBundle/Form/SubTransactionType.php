<?php

namespace TransactionBundle\Form;

use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\DBAL\LockMode;

/**
 * Description of SubTransactionType.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class SubTransactionType extends AbstractType
{
    private $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('type', Type\HiddenType::class, [], [
            'mapped' => array_get($builder->getOption('unmap'), 'type', true),
        ])->add('customerProduct', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'class' => 'DbBundle\Entity\CustomerProduct',
            'label' => 'fields.subTransaction.product',
            'required' => true,
            'choices' => [],
            'choice_label' => 'userName',
            'attr' => [
                'data-placeholder' => 'Select Product',
            ],
            'mapped' => array_get($builder->getOption('unmap'), 'customerProduct', true),
            'translation_domain' => 'TransactionBundle',
        ])->add('amount', Type\NumberType::class, [
            'label' => 'fields.subTransaction.amount',
            'translation_domain' => 'TransactionBundle',
            'scale' => 2,
            'required' => true,
            'attr' => [
                'class' => 'subtransaction-amount',
                'autocomplete' => 'off',
            ],
            'mapped' => array_get($builder->getOption('unmap'), 'amount', true),
        ])
        ->add('hasFee', Type\RadioType::class, [
            'label' => 'fields.hasFee',
            'translation_domain' => 'TransactionBundle',
            'required' => false,
            'property_path' => 'details[hasFee]',
            'mapped' => array_get($builder->getOption('unmap'), 'hasFee', true),
        ]);

        $builder->add('immutableCustomerProductData', Type\TextType::class);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $data = $event->getData();
            $form = $event->getForm();

            if (array_has($data, 'customerProduct')) {
                $assumed_value = explode(' ', $data['amount'])[0];
                $data['amount'] = is_numeric($assumed_value) ? (float) $assumed_value : $assumed_value;
                $event->setData($data);
            }

            if (array_has($data, 'customerProduct') && array_get($options['unmap'], 'customerProduct', true)) {
                $form->add('customerProduct', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                    'class' => 'DbBundle\Entity\CustomerProduct',
                    'label' => 'fields.subTransaction.product',
                    'required' => true,
                    'choices' => [$this->getCustomerProductRepository()->find($data['customerProduct'])],
                    'choice_label' => 'userName',
                    'attr' => [
                        'data-placeholder' => 'Select Product',
                    ],
                    'mapped' => array_get($form->getConfig()->getOption('unmap'), 'customerProduct', true),
                    'translation_domain' => 'TransactionBundle',
                ]);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DbBundle\Entity\SubTransaction',
            'validation_groups' => 'default',
            'allow_extra_fields' => true,
            'view' => false,
            'views' => [],
            'unmap' => [],
        ]);
    }

    public function finishView(\Symfony\Component\Form\FormView $view, \Symfony\Component\Form\FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        $view->vars['view'] = $options['view'];

        if ($form->getConfig()->getOption('view')) {
            foreach ($view->children as &$child) {
                $child->vars['view'] = true;
            }
        }

        foreach ($form->getConfig()->getOption('views') as $field => $isView) {
            $view->children[$field]->vars['view'] = $isView;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'subTransaction';
    }

    /**
     * Get customer product.
     *
     * @return \DbBundle\Repository\CustomerProductRepository
     */
    public function getCustomerProductRepository()
    {
        return $this->doctrine->getRepository('DbBundle:CustomerProduct');
    }
}
