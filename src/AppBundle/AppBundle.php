<?php

namespace AppBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use AppBundle\DependencyInjection\CompilerPass\DataTransferCompilerPass;
use AppBundle\DependencyInjection\CompilerPass\WidgetCompilerPass;
use DbBundle\Entity\Setting;
use PaymentBundle\Model\Bitcoin\SettingModel;

class AppBundle extends Bundle
{
    public function boot()
    {
        $env = $this->container->get('kernel')->getEnvironment();
        if ($env !== 'prod') {
            $this->getRouter()->getContext()->setBaseUrl('/app_' . $env . '.php');
        }
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DataTransferCompilerPass());
        $container->addCompilerPass(new WidgetCompilerPass());
    }

    public function registerMenu()
    {
        return [
            'dashboard' => [
                'permission' => ['ROLE_ADMIN'],
                'label' => $this->getTranslator()->trans('menus.Dashboard', [], 'AppBundle'),
                'uri' => $this->getRouter()->generate('app.dashboard_page'),
                'icon' => 'ti-dashboard',
            ],
        ];
    }

    public function registerRole()
    {
        return [
            'ROLE_API' => [
                'group' => 'roles.groups.system',
                'label' => 'roles.api',
                'translation_domain' => 'AppBundle',
            ],
            'ROLE_MAINTENANCE' => [
                'group' => 'roles.groups.system',
                'label' => 'roles.maintenance',
                'translation_domain' => 'AppBundle',
                'requirements' => ['ROLE_UPDATE_SETTINGS'],
            ],
            'ROLE_UPDATE_SETTINGS' => [
                'group' => 'roles.groups.system',
                'label' => 'roles.updateSettings',
                'translation_domain' => 'AppBundle',
            ],
            'ROLE_SCHEDULER' => [
                'group' => 'roles.groups.system',
                'label' => 'roles.scheduler',
                'translation_domain' => 'AppBundle',
            ],
            'ROLE_BITCOIN_SETTING' => [
                'group' => 'roles.groups.system',
                'label' => 'roles.bitcoinSetting',
                'translation_domain' => 'AppBundle',
            ],
        ];
    }

    public function registerSettingMenu()
    {
        return [
            'maintenance' => [
                'label' => 'settings.maintenance.label::AppBundle',
                'description' => 'settings.maintenance.description::AppBundle',
                'uri' => $this->getRouter()->generate('app.setting.maintenance_page'),
                'permission' => ['ROLE_MAINTENANCE'],
            ],
            'scheduler' => [
                'label' => 'settings.scheduler.label::AppBundle',
                'description' => 'settings.maintenance.description::AppBundle',
                'uri' => $this->getRouter()->generate('app.setting.scheduler_page'),
                'permission' => ['ROLE_SCHEDULER'],
            ]
        ];
    }

    public function registerDefaultSetting()
    {
        return [
            'scheduler.task' => [
                'auto_decline' => [
                    'autoDecline' => Setting::ENABLE_AUTO_DECLINE,
                    'minutesInterval' => Setting::SCHEDULER_DEFAULT_MIN,
                ],
            ],
            'bitcoin.setting' => [
                SettingModel::BITCOIN_DEPOSIT_CONFIGURATION => [
                    'autoDecline' => SettingModel::BITCOIN_ENABLE_AUTO_DECLINE,
                    'minutesInterval' => SettingModel::BITCOIN_DEFAULT_MIN,
                    'minimumAllowedDeposit' => SettingModel::BITCOIN_MINIMUM_ALLOWED_DEPOSIT,
                    'maximumAllowedDeposit' => SettingModel::BITCOIN_MAXIMUM_ALLOWED_DEPOSIT,
                ],
                SettingModel::BITCOIN_LOCK_PERIOD_SETTING => [
                    'autoLock' => Setting::ENABLE_AUTO_LOCK,
                    'minutesLockDownInterval' => Setting::LOCKDOWN_PERIOD_MIN,
                ],
                SettingModel::BITCOIN_WITHDRAWAL_CONFIGURATION => [
                    'minimumAllowedWithdrawal' => SettingModel::BITCOIN_MINIMUM_ALLOWED_WITHDRAWAL,
                    'maximumAllowedWithdrawal' => SettingModel::BITCOIN_MAXIMUM_ALLOWED_WITHDRAWAL,
                ],
            ],
            'maintenance.enabled' => 0,
            'level.max' => 10,
            'paymentOptions' => [
                'bank' => [
                    'code' => 'bank',
                    'label' => 'Bank Account',
                    'fields' => [
                        'account_id' => [
                            'code' => 'account_id',
                            'label' => 'Account ID',
                            'type' => \Symfony\Component\Form\Extension\Core\Type\TextType::class,
                        ],
                        'email' => [
                            'code' => 'email',
                            'label' => 'Email',
                            'type' => \Symfony\Component\Form\Extension\Core\Type\TextType::class,
                        ],
                    ],
                ],
            ],
            'dateFormat' => 'm/d/Y',
            'dateTimeFormat' => 'm/d/Y g:i:s A',
            'dashboard.default_widgets' => [
                "dwlSearch_20180129120953" => [
                    "type" => "dwlSearch",
                    "definition" => [
                        "id" => "dwlSearch_20180129120953"
                    ],
                    "properties" => [
                        "size" => 6,
                        "title" => "DWL search"
                    ]
                ],
                "customerCount_20180129120744" => [
                    "type" => "customerCount",
                    "definition" => [
                        "id" => "customerCount_20180129120744"
                    ],
                    "properties" => [
                        "size" => 6,
                        "limit"=> 5,
                        "title" => "New Members",
                        "status" => [
                            "registered"
                        ]
                    ]
                ],
                "memberProductRequestList_20181026215542" => [
                    "type" => "memberProductRequestList",
                    "definition" => [
                        "id" => "memberProductRequestList_20181026215542"
                    ],
                    "properties" => [
                        "size" => 6,
                        "limit"=> 10,
                        "title" => "Member Product Request List"
                    ]
                ],
                "customerSearch_20180129120450" => [
                    "type" => "customerSearch",
                    "definition" => [
                        "id" => "customerSearch_20180129120450"
                    ],
                    "properties" => [
                        "size" => 6,
                        "title" => "Member Search"
                    ]
                ],
                "transactionList_20180129120622" => [
                    "type" => "transactionList",
                    "definition" => [
                        "id" => "transactionList_20180129120622"
                    ],
                    "properties" => [
                        "size" => 6,
                        "sort" => "transaction.date",
                        "limit" => 0,
                        "title" => "Pending transactions",
                        "status" => [
                            1,
                            4
                        ]
                    ]
                ],
                "transactionList_20180129120914" => [
                    "type" => "transactionList",
                    "definition" => [
                        "id" => "transactionList_20180129120914"
                    ],
                    "properties" => [
                        "size" => 6,
                        "sort" => "transaction.date",
                        "limit" => 5,
                        "title" => "Recent Transactions",
                        "status" => [
                            2,
                            3,
                            "voided"
                        ]
                    ]
                ],
                "transactionSearch_20180129120432" => [
                    "type" => "transactionSearch",
                    "definition" => [
                        "id" => "transactionSearch_20180129120432"
                    ],
                    "properties" => [
                        "size" => 6,
                        "title" => "Transaction Search"
                    ]
                ]
            ],
            ''
        ];
    }

    public function registerSettingCodes()
    {
        return ['maintenance.enabled', 'scheduler.task'];
    }

    public function registerThemetViews()
    {
        return [
            'Default/index.html.twig',
            'Default/maintenance.html.twig',
            'Form/ubold.html.twig',
            'Security/login.html.twig',
            'Setting/maintenance.html.twig',
        ];
    }

    /**
     * Get router.
     *
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    protected function getRouter()
    {
        return $this->container->get('router');
    }

    /**
     * Get translator.
     *
     * @return \Symfony\Component\Translation\DataCollectorTranslator
     */
    protected function getTranslator()
    {
        return $this->container->get('translator');
    }
}
