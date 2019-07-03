<?php

namespace GatewayTransactionBundle\Form;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type;
use AppBundle\Form\Type as CType;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use DbBundle\Entity\GatewayTransaction;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Validator\Constraints\NotBlank;

class GatewayTransactionType extends AbstractType
{
    protected $doctrine;
    protected $router;

    public function __construct(Registry $doctrine, Router $router)
    {
        $this->doctrine = $doctrine;
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('_version', Type\HiddenType::class, [
                'property_path' => 'updatedAt',
                'model_transformer' => new CallbackTransformer(
                    function ($data) {
                        if ($data instanceof \DateTime) {
                            $data = $data->format('c');
                        }

                        return base64_encode($data);
                    },
                    function ($data) {
                        if (is_string($data)) {
                            $data = base64_decode($data);
                            $data = new \DateTime($data);
                        }

                        return $data;
                    }
                )
            ])
            ->add('number', Type\TextType::class, [
                'label' => 'fields.number',
                'required' => true,
                'attr' => [
                    'readonly' => true,
                ],
            ])
            ->add('date', Type\DateTimeType::class, [
                'label' => 'fields.date',
                'format' => 'MM/dd/yyyy h:mm:ss a',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('type', Type\HiddenType::class)
            ->add('paymentOption', CType\Select2Type::class, [
                'label' => 'fields.paymentOption',
                'attr' => [
                    'data-autostart' => 'true',
                    'data-ajax--url' => $this->router->generate('paymentoption.search'),
                    'data-ajax--type' => 'POST',
                    'data-minimum-input-length' => 0,
                    'data-length' => 10,
                    'data-ajax--cache' => 'true',
                ],
                'required' => true,
                'placeholder' => 'Select Payment Option',
            ])
            ->add('currency', CType\Select2Type::class, [
                'label' => 'fields.currency',
                'attr' => [
                    'data-autostart' => 'true',
                    'data-ajax--url' => $this->router->generate('currency.list_search'),
                    'data-ajax--type' => 'POST',
                    'data-minimum-input-length' => 0,
                    'data-length' => 10,
                    'data-ajax--cache' => 'true',
                ],
                'placeholder' => 'Select Currency',
                'required' => true,
            ])
            ->add('gateway', CType\Select2Type::class, [
                'label' => 'fields.gateway',
                'required' => true,
                'attr' => [
                    'class' => 'gateway',
                    'data-autostart' => 'true',
                    'data-ajax--type' => 'POST',
                    'data-minimum-input-length' => 0,
                    'data-length' => 10,
                    'data-ajax--cache' => 'true',
                ],
                'placeholder' => 'Select Gateway',
            ])
            ->add('amount', Type\NumberType::class, [
                'label' => 'fields.amount',
                'required' => true,
            ])
            ->add('fees', Type\FormType::class, [
                'label' => false,
            ])
            ->add('netAmount', Type\TextType::class, [
                'label' => 'fields.netAmount',
                'required' => true,
                'attr' => [
                    'readonly' => true,
                ],
            ])
            ->add('details', Type\FormType::class, [
                'label' => false,
            ])
        ;

        $builder->get('fees')->add('fee', Type\NumberType::class, [
                'label' => 'fields.fee',
                'required' => true,
            ])
        ;

        $builder->get('details')->add('notes', Type\TextareaType::class, [
                'required' => false,
            ])
        ;

        $builder->get('gateway')->addModelTransformer(new CallbackTransformer(
            function ($gateway) {
                if ($gateway instanceof \DbBundle\Entity\Gateway) {
                    $gateway = $this->getGatewayRepository()->find($gateway);

                    return [['id' => $gateway->getId(), 'text' => $gateway->getName()]];
                }

                return $gateway;
            },
            function ($gateway) {
                if ($gateway) {
                    return $this->getGatewayRepository()->find($gateway);
                }

                return null;
            }
        ));

        $builder->get('gateway')->addViewTransformer(new CallbackTransformer(
            function ($gateway) {
                if ($gateway instanceof \DbBundle\Entity\Gateway) {
                    $gateway = $this->getGatewayRepository()->find($gateway);

                    return [['id' => $gateway->getId(), 'text' => $gateway->getName()]];
                }

                return $gateway;
            },
            function ($gateway) {
                if ($gateway) {
                    return $this->getGatewayRepository()->find($gateway);
                }

                return null;
            }
        ));

        $builder->get('currency')->addViewTransformer(new CallbackTransformer(
            function ($currency) {
                if ($currency instanceof \DbBundle\Entity\Currency) {
                    $currency = $this->getCurrencyRepository()->find($currency);

                    return [['id' => $currency->getId(), 'text' => $currency->getName()]];
                }

                return $currency;
            },
            function ($currency) {
                if ($currency) {
                    return $this->getCurrencyRepository()->find($currency);
                }

                return null;
            }
        ));

        $builder->get('currency')->addModelTransformer(new CallbackTransformer(
            function ($currency) {
                if ($currency instanceof \DbBundle\Entity\Currency) {
                    $currency = $this->getCurrencyRepository()->find($currency);

                    return [['id' => $currency->getId(), 'text' => $currency->getName()]];
                }

                return $currency;
            },
            function ($currency) {
                if ($currency) {
                    return $this->getCurrencyRepository()->find($currency);
                }

                return null;
            }
        ));

        $builder->get('paymentOption')->addViewTransformer(new CallbackTransformer(
            function ($paymentOption) {
                if ($paymentOption instanceof \DbBundle\Entity\PaymentOption) {
                    $paymentOption = $this->getPaymentOptionRepository()->find($paymentOption);

                    return [['id' => $paymentOption->getCode(), 'text' => $paymentOption->getName()]];
                }

                return $paymentOption;
            },
            function ($paymentOption) {
                if ($paymentOption) {
                    return $this->getPaymentOptionRepository()->find($paymentOption);
                }

                return null;
            }
        ));

        $builder->get('paymentOption')->addModelTransformer(new CallbackTransformer(
            function ($paymentOption) {
                if ($paymentOption instanceof \DbBundle\Entity\PaymentOption) {
                    $paymentOption = $this->getPaymentOptionRepository()->find($paymentOption);

                    return [['id' => $paymentOption->getCode(), 'text' => $paymentOption->getName()]];
                }

                return $paymentOption;
            },
            function ($paymentOption) {
                if ($paymentOption) {
                    return $this->getPaymentOptionRepository()->find($paymentOption);
                }

                return null;
            }
        ));

        $builder->addEventListener(FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            if ($data instanceof GatewayTransaction) {
                if ($data->isApproved() || $data->isVoided()) {
                    $form->add('saveVoid', Type\SubmitType::class, [
                        'label' => 'form.saveVoid',
                        'attr' => [
                            'class' => 'pull-right btn-danger m-r-10',
                        ],
                    ]);

                    $form->get('details')->add('reason', Type\TextareaType::class, [
                        'required' => true,
                    ]);
                } elseif ($data->isPending()) {
                    $form->add('save', Type\SubmitType::class, [
                        'label' => 'form.save',
                        'translation_domain' => 'AppBundle',
                        'attr' => [
                            'class' => 'pull-right btn-success m-r-10',
                        ],
                    ])
                    ->add('saveApproved', Type\SubmitType::class, [
                        'label' => 'form.saveApproved',
                        'attr' => [
                            'class' => 'pull-right btn-success m-r-10',
                        ],
                    ]);
                }

                if ($data->isTransfer()) {
                    $form->add('gatewayTo', CType\Select2Type::class, [
                        'model_transformer' => new CallbackTransformer(
                            function ($gateway) {
                                if ($gateway instanceof \DbBundle\Entity\Gateway) {
                                    $gateway = $this->getGatewayRepository()->find($gateway);

                                    return [['id' => $gateway->getId(), 'text' => $gateway->getName()]];
                                }

                                return $gateway;
                            },
                            function ($gateway) {
                                if ($gateway) {
                                    return $this->getGatewayRepository()->find($gateway);
                                }

                                return null;
                            }
                        ),
                        'view_transformer' => new CallbackTransformer(
                            function ($gateway) {
                                if ($gateway instanceof \DbBundle\Entity\Gateway) {
                                    $gateway = $this->getGatewayRepository()->find($gateway);

                                    return [['id' => $gateway->getId(), 'text' => $gateway->getName()]];
                                }

                                return $gateway;
                            },
                            function ($gateway) {
                                if ($gateway) {
                                    return $this->getGatewayRepository()->find($gateway);
                                }

                                return null;
                            }
                        ),
                        'label' => 'fields.gatewayTo',
                        'required' => true,
                        'attr' => [
                            'class' => 'gateway',
                            'data-autostart' => 'true',
                            'data-ajax--type' => 'POST',
                            'data-minimum-input-length' => 0,
                            'data-length' => 10,
                            'data-ajax--cache' => 'true',
                        ],
                        'placeholder' => 'Select Gateway',
                    ]);

                    $form->get('fees')->add('feeTo', Type\NumberType::class, [
                        'label' => 'fields.feeTo',
                        'required' => true,
                    ]);

                    $form->add('netAmountTo', Type\TextType::class, [
                        'label' => 'fields.netAmountTo',
                        'required' => true,
                        'attr' => [
                            'readonly' => true,
                        ],
                    ]);

                    $form->add('amountTo', Type\NumberType::class, [
                        'label' => 'fields.amountTo',
                        'required' => true,
                    ]);
                }
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                if ($data) {
                    if ($form->has('details') && $form->get('details')->has('reason')) {
                        $unmap = [
                            'number' => false,
                            'date' => false,
                            'paymentOption' => false,
                            'type' => false,
                            'currency' => false,
                            'gateway' => false,
                            'gatewayTo' => false,
                            'amount' => false,
                            'amountTo' => false,
                            'fees' => [
                                'fee' => false,
                                'feeTo' => false,
                            ],
                            'netAmount' => false,
                            'netAmountTo' => false,
                            'details' => [
                                'notes' => false,
                            ]
                        ];

                        foreach ($unmap as $field => $isUnmap) {
                            if (!is_array($isUnmap)) {
                                if ($form->has($field)) {
                                    $config = $form->get($field)->getConfig();
                                    $options = $config->getOptions();

                                    $form->add(
                                        $field,
                                        get_class($config->getType()->getInnerType()),
                                        array_replace(
                                            $options,
                                            ['mapped' => $isUnmap]
                                        )
                                    );
                                }
                            } else {
                                foreach ($isUnmap as $child => $isChildUnmap) {
                                    if ($form->get($field)->has($child)) {
                                        $childConfig = $form->get($field)->get($child)->getConfig();
                                        $childOptions = $childConfig->getOptions();

                                        $form->get($field)->add(
                                            $child,
                                            get_class($childConfig->getType()->getInnerType()),
                                            array_replace(
                                                $childOptions,
                                                ['mapped' => $isChildUnmap]
                                            )
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GatewayTransaction::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'view' => false,
            'type' => null,
            'csrf_token_id' => 'gatewayTransaction',
            'translation_domain' => 'GatewayTransactionBundle',
            'validation_groups' => function (FormInterface $form) {
                $data = $form->getData();
                $validationGroups = ['Default'];

                if ($data->isTransfer()) {
                    $validationGroups[] = 'transfer';
                }

                return $validationGroups;
            }
        ]);
    }

    public function buildView(\Symfony\Component\Form\FormView $view, \Symfony\Component\Form\FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars = array_merge($view->vars, [
            'type' => $options['type'],
        ]);
    }

    public function finishView(\Symfony\Component\Form\FormView $view, \Symfony\Component\Form\FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        $data = $form->getData();

        $views = [
            'number' => true,
            'date' => true,
            'type' => true,
            'paymentOption' => true,
            'currency' => true,
            'gateway' => true,
            'gatewayTo' => true,
            'amount' => true,
            'amountTo' => true,
            'fees' => [
                'fee' => true,
                'feeTo' => true,
            ],
            'netAmount' => true,
            'netAmountTo' => true,
            'details' => [
                'notes' => true,
                'reason' => true,
            ]
        ];

        if ($data->isVoided() || $data->isApproved()) {
            foreach ($views as $field => $isView) {
                if (is_array($isView)) {
                    foreach ($isView as $child => $isChildView) {
                        if (isset($view->children[$field][$child])) {
                            $view->children[$field][$child]->vars['view'] = $isChildView;
                        }
                    }
                } else {
                    if ($form->has($field)) {
                        $view->children[$field]->vars['view'] = $isView;
                    }
                }
            }
        }

        if ($data->isApproved()) {
            $view->children['details']['reason']->vars['view'] = false;
        }

        if ($data->isVoided()) {
            $view->vars['view'] = true;
        }
    }

    public function getBlockPrefix()
    {
        return 'GatewayTransaction';
    }

    public function getGatewayRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Gateway');
    }

    public function getCurrencyRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Currency');
    }

    public function getPaymentOptionRepository()
    {
        return $this->doctrine->getRepository('DbBundle:PaymentOption');
    }
}
