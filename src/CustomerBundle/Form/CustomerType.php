<?php

namespace CustomerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Validator\Constraints\Valid;
use AppBundle\Form\Type as CType;
use DbBundle\Entity\Customer;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;
use AppBundle\Manager\SettingManager;
use DbBundle\Entity\CustomerGroups;

class CustomerType extends AbstractType
{
    /**
     * @var \Doctrine\Bundle\DoctrineBundle\Registry
     */
    protected $doctrine;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    protected $router;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * @var \AppBundle\Manager\SettingManager
     */
    protected $settingManager;

    public function __construct(
        Registry $doctine,
        Router $router,
        SettingManager $settingManager,
        TranslatorInterface $translator
    ) {
        $this->doctrine = $doctine;
        $this->router = $router;
        $this->translator = $translator;
        $this->settingManager = $settingManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $maxLevel = $this->settingManager->getSetting('level.max');
        $levels = [];
        for ($i = 1; $i <= $maxLevel; ++$i) {
            $levels[$this->getTranslator()->trans('fields.level', [], 'CustomerBundle') . " $i"] = $i;
        }

        $builder
            ->add('guestType', Type\HiddenType::class, [
                'label' => 'hidden',
                'required' => false,
                'data' => $options['guestType'],
                'mapped' => false,
                'translation_domain' => 'CustomerBundle',
            ])
            ->add('fName', Type\TextType::class, [
                'label' => 'fields.fName',
                'required' => true,
                'translation_domain' => 'CustomerBundle',
            ])
            ->add('mName', Type\TextType::class, [
                'label' => 'fields.mName',
                'required' => false,
                'translation_domain' => 'CustomerBundle',
                'empty_data' => '',
            ])
            ->add('lName', Type\TextType::class, [
                'label' => 'fields.lName',
                'required' => true,
                'translation_domain' => 'CustomerBundle',
            ])
            ->add('birthDate', Type\DateType::class, [
                'label' => 'fields.birthDate',
                'widget' => 'single_text',
                'format' => 'M/dd/yyyy',
                'required' => true,
                'translation_domain' => 'CustomerBundle',
            ])
            ->add('country', CType\Select2Type::class, [
                'label' => 'fields.country',
                'translation_domain' => 'CustomerBundle',
                'attr' => [
                    'data-autostart' => 'true',
                    'data-ajax--url' => $this->router->generate('country.list_search'),
                    'data-ajax--type' => 'POST',
                    'data-minimum-input-length' => 0,
                    'data-length' => 10,
                    'data-ajax--cache' => 'true',
                    'data-id-column' => 'id',
                ],
                'required' => true,
            ])
            ->add('currency', CType\Select2Type::class, [
                'label' => 'fields.currency',
                'translation_domain' => 'CustomerBundle',
                'attr' => [
                    'data-autostart' => 'true',
                    'data-ajax--url' => $this->router->generate('currency.list_search'),
                    'data-ajax--type' => 'POST',
                    'data-minimum-input-length' => 0,
                    'data-length' => 10,
                    'data-ajax--cache' => 'true',
                    'data-id-column' => 'id',
                ],
                'required' => true,
            ])
            ->add('balance', Type\NumberType::class, [
                'label' => 'fields.balance',
                'translation_domain' => 'CustomerBundle',
                'required' => true,
                'scale' => 2,
            ])
            ->add('joinedAt', Type\DateTimeType::class, [
                'label' => 'fields.joinedAt',
                'widget' => 'single_text',
                'format' => 'M/dd/yyyy h:mm:ss a',
                'required' => true,
                'translation_domain' => 'CustomerBundle',
            ])
            ->add('groups', CType\Select2Type::class, [
                'label' => 'fields.group',
                'translation_domain' => 'CustomerBundle',
                'attr' => [
                    'data-autostart' => 'true',
                    'data-ajax--url' => $this->router->generate('customer.group_list'),
                    'data-ajax--type' => 'POST',
                    'data-minimum-input-length' => 0,
                    'data-length' => 10,
                    'data-ajax--cache' => 'true',
                    'data-id-column' => 'id',
                ],
                'multiple' => true,
                'required' => true,
                'text' => '{name}',
            ])
            ->add('affiliate', CType\Select2Type::class, [
                'label' => 'fields.affiliate.label',
                'translation_domain' => 'CustomerBundle',
                'attr' => [
                    'data-autostart' => 'true',
                    'data-ajax--type' => 'POST',
                    'data-ajax--url' => $this->router->generate('customer.list_search', ['isAffiliate' => '1']),
                    'data-minimum-input-length' => 2,
                    'data-length' => 10,
                    'data-ajax--cache' => 'true',
                ],
                'required' => false,
                'key' => 'id',
                'text' => '{fName} {lName}',
                'placeholder' => 'fields.affiliate.placeholder',
            ])
            ->add('isAffiliate', CType\SwitchType::class, [
                'label' => 'fields.isAffiliate',
                'required' => false,
                'translation_domain' => 'CustomerBundle',
            ])
            ->add('isCustomer', CType\SwitchType::class, [
                'label' => 'fields.isCustomer',
                'required' => false,
                'translation_domain' => 'CustomerBundle',
            ])
            ->add('btnGroup', CType\GroupType::class, [
            ])
        ;

        $byReference = false;
        if ($builder->getData() instanceof \DbBundle\Entity\Customer && $builder->getData()->getUser() instanceof \DbBundle\Entity\User) {
            if ($builder->getData()->getUser()->getId()) {
                $byReference = true;
            }
        }

        $builder->add(
            $builder
                ->create('user', \Symfony\Component\Form\Extension\Core\Type\FormType::class, [
                    'by_reference' => $byReference,
                    'constraints' => [new Valid()],
                    'data_class' => \DbBundle\Entity\User::class,
                ])
                ->add('username', Type\TextType::class, [
                    'label' => 'fields.username',
                    'required' => true,
                    'translation_domain' => 'UserBundle',
                ])
                ->add('changePassword', CType\SwitchType::class, [
                    'label' => 'fields.changePassword',
                    'required' => false,
                    'translation_domain' => 'UserBundle',
                    'data' => false,
                    'mapped' => false,
                ])
                ->add('password', Type\RepeatedType::class, [
                    'type' => Type\PasswordType::class,
                    'required' => true,
                    'invalid_message' => 'user.password.mismatch',
                    'first_options' => ['label' => 'fields.password', 'translation_domain' => 'CustomerBundle'],
                    'second_options' => ['label' => 'fields.confirmPassword', 'translation_domain' => 'CustomerBundle'],
                ])
                ->add('email', Type\EmailType::class, [
                    'label' => 'fields.email',
                    'required' => true,
                    'translation_domain' => 'UserBundle',
                ])
                ->add('isActive', CType\SwitchType::class, [
                    'label' => 'fields.isActive',
                    'required' => false,
                    'translation_domain' => 'UserBundle',
                ])
                ->add('type', Type\HiddenType::class, [
                    'label' => 'fields.type',
                    'required' => false,
                    'translation_domain' => 'UserBundle',
                    'data' => 1,
                ])
        );

        $builder->get('currency')->addViewTransformer(new CallbackTransformer(
            function ($currency) {
                if (!($currency instanceof \DbBundle\Entity\Currency) && $currency !== null) {
                    $currency = $this->getCurrencyRepository()->find($currency);
                }
                if ($currency !== null) {
                    return [['id' => $currency->getId(), 'text' => $currency->getName()]];
                }

                return [];
            },
            function ($currency) {
                return $currency;
            }
        ));

        $builder->get('country')->addViewTransformer(new CallbackTransformer(
            function ($country) {
                if (!($country instanceof \DbBundle\Entity\Country) && $country !== null) {
                    $country = $this->getCountryRepository()->find($country);
                }
                if ($country !== null) {
                    return [['id' => $country->getId(), 'text' => $country->getName()]];
                }

                return [];
            },
            function ($country) {
                return $country;
            }
        ));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $customer = $event->getData();
            $form = $event->getForm();
            $guestType = $form->getConfig()->getOption('guestType');

            if (!$customer) {
                $form->get('user')->remove('changePassword');
            } else {
                $form->add('clientIP', Type\TextType::class, [
                    'label' => 'fields.clientIP',
                    'required' => false,
                    'mapped' => false,
                    'data' => $customer->getUser()->getPreference('ipAddress'),
                    'translation_domain' => 'UserBundle',
                    'attr' => [
                        'readonly' => true,
                    ],
                ]);
                $form->add('origin', Type\TextType::class, [
                    'label' => 'fields.origin',
                    'required' => false,
                    'mapped' => false,
                    'data' => $customer->getUser()->getPreference('originUrl'),
                    'translation_domain' => 'UserBundle',
                    'attr' => [
                        'readonly' => true,
                    ],
                ]);
                $form->add('referrer', Type\TextType::class, [
                    'label' => 'fields.referrer',
                    'required' => false,
                    'mapped' => false,
                    'data' => $customer->getUser()->getPreference('referrer'),
                    'translation_domain' => 'UserBundle',
                    'attr' => [
                        'readonly' => true,
                    ],
                ]);
                $form->add('affiliateCode', Type\TextType::class, [
                    'label' => 'fields.affiliateCode',
                    'required' => false,
                    'mapped' => false,
                    'data' => $customer->getUser()->getPreference('affiliateCode'),
                    'translation_domain' => 'UserBundle'
                ]);
                $form->add('promoCode', Type\TextType::class, [
                    'required' => false,
                    'mapped' => false,
                    'data' => $customer->getUser()->getPreference('promoCode'),
                    'translation_domain' => 'UserBundle',
                ]);
                $form->remove('isCustomer');
                $form->remove('isAffiliate');
            }

            if ($guestType == Customer::CUSTOMER) {
                if (!$customer) {
                    $form->remove('isCustomer');
                } else if (!$customer->getIsAffiliate()) {
                    $form->get('btnGroup')->add('convert', Type\ButtonType::class, [
                        'label' => 'form.convertToAffiliate',
                        'translation_domain' => 'CustomerBundle',
                        'attr' => [
                            'class' => 'btn-info pull-left',
                        ],
                    ]);
                }
                $form->get('btnGroup')->add('cancelCustomer', Type\ButtonType::class, [
                    'label' => 'form.cancel',
                    'translation_domain' => 'CustomerBundle',
                    'attr' => [
                        'class' => 'btn-inverse pull-right',
                    ],
                ]);
            } else {
                $form->remove('groups');
                $form->remove('affiliate');

                if (!$customer) {
                    $form->remove('isAffiliate');
                } else if (!$customer->getIsCustomer()) {
                    $form->get('btnGroup')->add('convert', Type\ButtonType::class, [
                        'label' => 'form.convertToCustomer',
                        'translation_domain' => 'CustomerBundle',
                        'attr' => [
                            'class' => 'btn-info',
                        ],
                    ]);
                }
                $form->get('btnGroup')->add('cancelAffiliate', Type\ButtonType::class, [
                    'label' => 'form.cancel',
                    'translation_domain' => 'CustomerBundle',
                    'attr' => [
                        'class' => 'btn-inverse pull-right',
                    ],
                ]);
            }

            $form->get('btnGroup')->add('save', Type\SubmitType::class, [
                'label' => 'form.save',
                'translation_domain' => 'AppBundle',
                'attr' => [
                    'class' => 'btn-success pull-right',
                    'style' => 'margin-right: 10px;',
                ],
            ]);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $customer = $event->getData();
            $form = $event->getForm();

            if (is_null($customer->getMName())) {
                $customer->setMName('');
            }
        });

        if ($options['guestType'] == Customer::CUSTOMER) {
            if ($builder->getData()) {
                $builder->get('affiliate')->addModelTransformer(new CallbackTransformer(
                    function ($data) {
                        if ($data instanceof \DbBundle\Entity\Customer
                            && method_exists($data, '__isInitialized')
                            && $data->__isInitialized() === false
                        ) {
                            $data->__load();
                        }

                        return $data;
                    },
                    function ($data) {
                        if ($data && !($data instanceof \DbBundle\Entity\Customer)) {
                            return $this->getCustomerRepository()->find($data);
                        }

                        return null;
                    }
                ));

                $builder->get('affiliate')->addViewTransformer(new CallbackTransformer(
                    function ($data) {
                        if ($data instanceof \DbBundle\Entity\Customer) {
                            return [[
                                'id' => $data->getId(),
                                'fName' => $data->getFName(),
                                'lName' => $data->getLName(),
                            ], ];
                        }

                        if (!is_null($data)) {
                            if ($data) {
                                $data = $this->getCustomerRepository()->findById($data, \Doctrine\ORM\Query::HYDRATE_ARRAY);

                                return [[
                                    'id' => $data['id'],
                                    'fName' => $data['fName'],
                                    'lName' => $data['lName'],
                                ], ];
                            }

                            return null;
                        }

                        return $data;
                    },
                    function ($data) {
                        return $data;
                    }
                ));
            }
        }

        $builder->get('currency')->addModelTransformer(new CallbackTransformer(
            function ($data) {
                if ($data instanceof \DbBundle\Entity\Currency) {
                    return $data->getId();
                }

                return $data;
            },
            function ($data) {
                return $this->getCurrencyRepository()->find($data);
            }
        ));

        $builder->get('country')->addModelTransformer(new CallbackTransformer(
            function ($data) {
                if ($data instanceof \DbBundle\Entity\Country) {
                    return $data->getId();
                }

                return $data;
            },
            function ($data) {
                return $this->getCountryRepository()->find($data);
            }
        ));

        if ($options['guestType'] == Customer::CUSTOMER) {
            $builder->get('groups')->addModelTransformer(new CallbackTransformer(
                function ($items) {
                    if (!is_array($items) && !($items instanceof \Doctrine\ORM\PersistentCollection)) {
                        return [];
                    }

                    if ($items instanceof \Doctrine\ORM\PersistentCollection) {
                        $this->doctrine->getManager()->initializeObject($items);
                    }

                    $parsedItems = [];
                    foreach ($items as $item) {
                        if ($item instanceof \DbBundle\Entity\CustomerGroup) {
                            $parsedItems[] = ['id' => $item->getId(), 'name' => $item->getName()];
                        } elseif (is_numeric($item)) {
                            $parsedItems[] = $item;
                        }
                    }

                    return $parsedItems;
                },
                function ($items) {
                    if (is_null($items) || !is_array($items)) {
                        return [];
                    }
                    $parsedItems = new \Doctrine\Common\Collections\ArrayCollection();
                    foreach ($items as $item) {
                        if (is_numeric($item)) {
                            $parsedItems->add($this->getCustomerGroupRepository()->find($item));
                        }
                    }

                    return $parsedItems;
                }
            ));
            $builder->get('groups')->addViewTransformer(new CallbackTransformer(
                function ($items) {
                    $predefinedItems = [];
                    if (is_null($items) || !is_array($items)) {
                        return $predefinedItems;
                    }
                    foreach ($items as $item) {
                        if (is_array($item) && array_has($item, 'id') && array_has($item, 'name')) {
                            $predefinedItems[] = $item;
                        } elseif (is_array($item) && array_has($item, 'id')) {
                            $group = $this->getCustomerGroupRepository()->find($item['id']);
                            $predefinedItems[] = ['id' => $group->getId(), 'name' => $group->getName()];
                        } elseif (is_numeric($item)) {
                            $group = $this->getCustomerGroupRepository()->find($item);
                            $predefinedItems[] = ['id' => $group->getId(), 'name' => $group->getName()];
                        }
                    }
                    
                    return $predefinedItems;
                },
                function ($items) {
                    return $items;
                }
            ));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'DbBundle\Entity\Customer',
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'customer',
            'validation_groups' => 'default',
            'cascade_validation' => true,
            'guestType' => null,
            'allow_extra_fields' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'Customer';
    }

    /**
     * Get customer product.
     *
     * @return \DbBundle\Repository\CustomerRepository
     */
    protected function getCustomerRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Customer');
    }

    /**
     * Get customer group repository
     *
     * @return \DbBundle\Repository\CustomerGroupRepository
     */
    protected function getCustomerGroupRepository()
    {
        return $this->doctrine->getRepository('DbBundle:CustomerGroup');
    }

    /**
     * Get currency repository.
     *
     * @return \DbBundle\Repository\CurrencyRepository
     */
    protected function getCurrencyRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Currency');
    }

    /**
     * Get country repository.
     *
     * @return \DbBundle\Repository\CountryRepository
     */
    protected function getCountryRepository()
    {
        return $this->doctrine->getRepository('DbBundle:Country');
    }

    /**
     * Shortcut to return the Doctrine Registry service.
     *
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     *
     * @throws \LogicException If DoctrineBundle is not available
     */
    protected function getDoctrine()
    {
        return $this->doctrine;
    }

    protected function getSettingManager()
    {
        return $this->settingManager;
    }

    /**
     * Get translator.
     *
     * @return \Symfony\Component\Translation\DataCollectorTranslator
     */
    protected function getTranslator()
    {
        return $this->translator;
    }
}
