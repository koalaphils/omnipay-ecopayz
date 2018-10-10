<?php

namespace TransactionBundle\Form;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Form\Type as CType;
use Symfony\Component\Form\CallbackTransformer;
use DbBundle\Entity\Transaction;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use JMS\Serializer\SerializerInterface;
use AppBundle\Manager\SettingManager;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;


/**
 * Description of TransactionType.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class TransactionType extends AbstractType
{
    private $doctrine;
    private $router;
    private $settingManager;
    private $jmsSerializer;

    public function __construct(Registry $doctrine, Router $router, SettingManager $settingManager, SerializerInterface $jmsSerializer)
    {
        $this->doctrine = $doctrine;
        $this->router = $router;
        $this->settingManager = $settingManager;
        $this->jmsSerializer = $jmsSerializer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('number', Type\TextType::class, [
            'label' => 'fields.number',
            'translation_domain' => 'TransactionBundle',
            'required' => true,
            'mapped' => array_get($builder->getOption('unmap'), 'number', true),
            'attr' => [
                'readonly' => true,
            ],
        ])->add('customer', CType\Select2Type::class, [
            'label' => 'fields.customer',
            'required' => true,
            'attr' => [
                'data-autostart' => 'true',
                'data-ajax--url' => $this->router->generate('customer.list'),
                'data-ajax--type' => 'POST',
                'data-minimum-input-length' => 2,
                'data-length' => 10,
                'data-ajax--cache' => 1,
                'data-minimum-results-for-search' => 'Infinity',
                'data-use-template-result' => 'customerTemplateResult',
                'data-use-template-selection' => 'customerTemplateSelection',
            ],
            'placeholder' => 'Select Member',
            'translation_domain' => 'TransactionBundle',
            'mapped' => array_get($builder->getOption('unmap'), 'customer', true),
        ])->add('currency', Type\TextType::class, [
            'label' => 'fields.currency',
            'required' => false,
            'translation_domain' => 'TransactionBundle',
            'mapped' => false,
        ])->add('date', Type\DateTimeType::class, [
            'label' => 'fields.date',
            'format' => 'MM/dd/yyyy h:mm:ss a',
            'widget' => 'single_text',
            'required' => true,
            'translation_domain' => 'TransactionBundle',
            'mapped' => array_get($builder->getOption('unmap'), 'date', true),
        ])->add('customerFee', Type\NumberType::class, [
            'label' => 'fields.memberFee',
            'translation_domain' => 'TransactionBundle',
            'required' => false,
            'attr' => [
                'autocomplete' => 'off',
            ],
            'property_path' => 'fees[customer_fee]',
            'mapped' => array_get($builder->getOption('unmap'), 'customerFee', true),
        ])->add('companyFee', Type\NumberType::class, [
            'label' => 'fields.companyFee',
            'translation_domain' => 'TransactionBundle',
            'required' => false,
            'attr' => [
                'autocomplete' => 'off',
            ],
            'property_path' => 'fees[company_fee]',
            'mapped' => array_get($builder->getOption('unmap'), 'companyFee', true),
        ]);

        if ($this->getSettingManager()->getSetting('transaction.paymentGateway') === 'customer-level') {
            $builder->add('gateway', Type\TextType::class, [
                'label' => 'fields.gateway',
                'required' => false,
                'translation_domain' => 'TransactionBundle',
                'mapped' => false,
            ]);
        } elseif ($this->getSettingManager()->getSetting('transaction.paymentGateway') === 'customer-currency'
            || $this->getSettingManager()->getSetting('transaction.paymentGateway') === 'customer-group'
        ) {
            $builder->add('gateway', CType\Select2Type::class, [
                'label' => 'fields.gateway',
                'required' => false,
                'attr' => [
                    'data-autostart' => 'true',
                    'data-ajax--type' => 'POST',
                    'data-minimum-input-length' => 0,
                    'data-length' => 10,
                    'data-ajax--cache' => 1,
                    'data-minimum-results-for-search' => 'Infinity',
                ],
                'placeholder' => 'Select Payment Gateway',
                'translation_domain' => 'TransactionBundle',
                'mapped' => array_get($builder->getOption('unmap'), 'gateway', true),
                'text' => '{name}',
            ]);
        }

        $builder->add('subTransactions', Type\CollectionType::class, [
            'entry_type' => SubTransactionType::class,
            'constraints' => [new \Symfony\Component\Validator\Constraints\Valid()],
            'entry_options' => [
                'view' => $builder->getData()->getId() ? true : false,
                'views' => array_get($builder->getOption('views', []), 'subTransactions', []),
                'unmap' => array_get($builder->getOption('unmap'), 'subTransactions', []),
            ],
            'prototype' => true,
            'allow_add' => true,
            'by_reference' => $builder->getData()->getId() ? true : false,
            'mapped' => array_get(array_get($builder->getOption('unmap'), 'subTransactions', ['__this' => true]), '__this', true),
            'error_bubbling' => false,
        ]);

        $builder->add('notes', Type\TextareaType::class, [
            'required' => false,
            'property_path' => 'details[notes]',
        ]);

        $builder->add('reasonToVoidOrDecline', Type\HiddenType::class, [
            'required' => false,
            'property_path' => 'details[reasonToVoidOrDecline]',
            'constraints' => [
                new \Symfony\Component\Validator\Constraints\NotBlank([
                    'message' => 'Reason is required.',
                    'groups' => 'isForVoidingOrDecline',
                ])
            ]
        ]);

        $this->showVoidOrDeclineReason($builder);

        $builder->add('actions', CType\GroupType::class, [
            'attr' => [
                'class' => 'pull-right',
            ],
        ]);

        foreach ($builder->getOption('actions') as $key => $action) {
            $builder->get('actions')->add('btn_' . strtolower($action['label']), Type\SubmitType::class, [
                'label' => $action['label'],
                'translation_domain' => false,
                'attr' => [
                    'value' => "$key",
                    'class' => array_get($action, 'class', ''),
                ],
            ]);
        }

        $builder->get('customer')->addViewTransformer(new CallbackTransformer(
            function ($customer) {
                if ($customer) {
                    $entity = $this->getCustomerRepository()->findById($customer);

                    return [$entity];
                }

                return $customer;
            },
            function ($customer) {
                return $customer;
            }
        ));

        $builder->get('customer')->addModelTransformer(new CallbackTransformer(
            function ($customer) {
                if ($customer && $customer->getId()) {
                    $data = $this->getCustomerRepository()->findById($customer->getId(), \Doctrine\ORM\Query::HYDRATE_OBJECT);

                    return $data;
                }

                return null;
            },
            function ($customer) {
                $customerEntity = null;
                if ($customer) {
                    $customerEntity = $this->getCustomerRepository()->find($customer);
                }

                return $customerEntity;
            }
        ));

        $builder->get('gateway')->addModelTransformer(new CallbackTransformer(
            function ($data) {
                if ($data instanceof \DbBundle\Entity\Gateway && method_exists($data, '__isInitialized') && $data->__isInitialized() === false) {
                    $data = $this->getGatewayRepository()->findById($data->getId());
                }
                $context = \JMS\Serializer\SerializationContext::create();
                $context->setGroups(['Default', 'balance', 'details']);

                return json_decode($this->jmsSerializer->serialize($data, 'json', $context), true);
            },
            function ($data) {
                if ($data && !($data instanceof \DbBundle\Entity\Gateway)) {
                    return $this->getGatewayRepository()->find($data);
                }

                return null;
            }
        ));

        $builder->get('gateway')->addViewTransformer(new CallbackTransformer(
            function ($data) {
                if ($data === null) {
                    return [];
                }

                return [$data];
            },
            function ($data) {
                return $data;
            }
        ));

        $this->showImmutablePaymentOptionData($builder, $options);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            if ($form->getData()->getId() !== null) {
                $customer = $form->getData()->getCustomer()->getId();
            } else {
                $customer = array_get($event->getData(), 'customer', null);
            }

            if ($form->has('immutablePaymentOptionOnTransactionData')) {
                $form->remove('immutablePaymentOptionOnTransactionData');
            }

            if ($customer === null) {
                $form->add('paymentOption', Type\ChoiceType::class, []);
            } else {
                $customer = $this->getCustomerRepository()->find($customer);
                $options = [];
                $this->doctrine->getManager()->initializeObject($customer->getPaymentOptions());
                foreach ($customer->getPaymentOptions() as $option) {
                    $options[] = $option;
                }
                $form->add('paymentOption', Type\ChoiceType::class, [
                    'choices' => $options,
                    'choice_label' => function ($value, $key) {
                        if ($value !== null) {
                            return $value->getType();
                        }

                        return '';
                    },
                    'choice_value' => function ($value) {
                        if ($value !== null) {
                            return $value->getId();
                        }

                        return '';
                    },
                    'mapped' => $form->getData()->getId() === null ? true : false,
                ]);
            }
        });
    }

    private function showImmutablePaymentOptionData($builder, $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $transaction = $event->getData();
            $customer = $transaction->getCustomer();
            $form = $event->getForm();

            if (!$transaction->isNew()) {
                if ($transaction->getPaymentOptionOnTransaction()) {
                    $form->add('immutablePaymentOptionOnTransactionData', Type\TextType::class);
                }
            }

            if ($transaction->isClosedForFurtherProcessing() && !$transaction->isNew()) {
                if (array_get($options,'isForVoidingOrDecline')) {
                    $form->add('immutablePaymentOptionData', Type\TextType::class, [
                        'mapped' => false,
                    ]);
                } else {
                    $form->add('immutablePaymentOptionData', Type\TextType::class);
                }
            } elseif ($customer === null) {
                $form->add('paymentOption', Type\ChoiceType::class, []);
            } else {
                $options = [];
                $this->doctrine->getManager()->initializeObject($customer->getPaymentOptions());
                foreach ($customer->getPaymentOptions() as $option) {
                    $options[] = $option;
                }
                $form->add('paymentOption', Type\ChoiceType::class, [
                    'choices' => $options,
                    'choice_label' => function ($value, $key) {
                        if ($value !== null) {
                            return $value->getType();
                        }

                        return '';
                    },
                    'choice_value' => function ($value) {
                        if ($value !== null) {
                            return $value->getId();
                        }

                        return '';
                    },
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
            'data_class' => 'DbBundle\Entity\Transaction',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'transaction',
            'validation_groups' => function () {
                $groups = ['default'];
                if ($this->getSettingManager()->getSetting('transaction.paymentGateway', 'customer-level') !== 'customer-level') {
                    $groups[] = 'withGateway';
                }

                return $groups;
            },
            'cascade_validation' => true,
            'constraints' => [new \Symfony\Component\Validator\Constraints\Valid()],
            'actions' => [],
            'unmap' => [],
            'views' => [],
            'addSubtransaction' => true,
            'isForVoidingOrDecline' => false,
        ]);
    }

    public function finishView(\Symfony\Component\Form\FormView $view, \Symfony\Component\Form\FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        $data = $form->getData();

        $view->children['subTransactions']->vars['addSubtransaction'] = $options['addSubtransaction'];

        if ($data->getCustomer()) {
            if ($data->getCurrency() instanceof \DbBundle\Entity\Currency) {
                $view->children['currency']->vars['value'] = $data->getCurrency()->getName();
            } else {
                $view->children['currency']->vars['value'] = $data->getCustomer()->getCurrency()->getName();
            }
        }

        foreach ($form->getConfig()->getOption('views') as $field => $isView) {
            if (!is_array($isView)) {
                $view->children[$field]->vars['view'] = $isView;
            }
        }
        $view->children['currency']->vars['view'] = true;
        if ($this->getSettingManager()->getSetting('transaction.paymentGateway') === 'customer-level') {
            $view->children['gateway']->vars['view'] = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'Transaction';
    }

    /**
     * Get customer product.
     *
     * @return \DbBundle\Repository\CustomerRepository
     */
    public function getCustomerRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Customer');
    }

    /**
     * Get Setting Manager.
     *
     * @return \AppBundle\Manager\SettingManager
     */
    public function getSettingManager()
    {
        return $this->settingManager;
    }

    /**
     * Get gateway repository.
     *
     * @return \DbBundle\Repository\GatewayRepository
     */
    public function getGatewayRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Gateway');
    }

    /**
     * @return \DbBundle\Repository\CurrencyRepository
     */
    public function getCurrencyRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Currency');
    }

    private function showVoidOrDeclineReason(FormBuilderInterface $builder)
    {
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $transaction = $event->getData();
            if ($transaction->isVoided() || $transaction->isDeclined()) {
                $form = $event->getForm();
                $form->add('reasonToVoidOrDecline', Type\TextAreaType::class, [
                    'required' => false,
                    'property_path' => 'details[reasonToVoidOrDecline]',
                ]);
            }
        });
    }
}
