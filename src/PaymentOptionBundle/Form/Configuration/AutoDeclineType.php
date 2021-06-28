<?php

declare(strict_types = 1);

namespace PaymentOptionBundle\Form\Configuration;

use AppBundle\Manager\SettingManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;

class AutoDeclineType extends AbstractType
{
    /**
     * @var SettingManager
     */
    private $settingManager;

    public function __construct(SettingManager $settingManager)
    {
        $this->settingManager = $settingManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $statuses = $this->settingManager->getSetting('transaction.status');
        $statusChoices = [
        ];
        foreach ($statuses as $key => $value) {
            $statusChoices[$value['label']] = $key;
        }

        $builder
            ->add('interval', Type\TextType::class, [
                'label' => 'settings.bitcoin.fields.minutesInterval',
                'required' => false,
                'translation_domain' => 'AppBundle',
            ])
            ->add('status', Type\ChoiceType::class, [
                'label' => 'settings.scheduler.fields.status',
                'required' => false,
                'translation_domain' => 'AppBundle',
                'choices' => $statusChoices,
            ])
        ;
    }
}