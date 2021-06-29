<?php

namespace AppBundle\Listener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type;

class WidgetFormSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        /* @var $widget \AppBundle\Widget\AbstractWidget */
        $widget = $form->getConfig()->getOption('widget');
        if ($widget !== null) {
            foreach ($widget['properties'] as $name => $definition) {
                $type = $definition['type'] ?? Type\TextType::class;
                $form->add($name, $type, $definition['options'] ?? []);
            }
        }
    }
}
