<?php

declare(strict_types = 1);

namespace PaymentOptionBundle\Form\Configuration;

use AppBundle\Event\FormExtendEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigurationType extends AbstractType
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('autoDecline', AutoDeclineType::class);
    }
}