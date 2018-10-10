<?php

namespace DbBundle\Serializer;

use JMS\Serializer\EventDispatcher\ObjectEvent;
use DbBundle\Repository\DWLRepository;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Context;

class TransactionSerializerSubscriber implements \JMS\Serializer\EventDispatcher\EventSubscriberInterface
{
    protected $settingManager;
    protected $entityManager;

    public function __construct(
        \AppBundle\Manager\SettingManager $settingManager,
        EntityManager $entityManager
    ) {
        $this->settingManager = $settingManager;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ['event' => 'serializer.post_serialize', 'method' => 'onPostSerializeMethod'],
        ];
    }

    public function onPostSerializeMethod(ObjectEvent $event)
    {
        $object = $event->getObject();
        if (!($object instanceof \DbBundle\Entity\Transaction)) {
            return;
        }
        /* @var $visitor \JMS\Serializer\JsonSerializationVisitor */
        $visitor = $event->getVisitor();
        $status = $this->settingManager->getSetting('transaction.status.' . $object->getStatus());
        $visitor->setData('status', ['id' => $object->getStatus(), 'label' => $status['label']]);

        $context = $event->getContext();
        $groups = $this->getGroupsFor($context->attributes->get('groups')->get(), $context);
        if (in_array('dwl', $groups) && $object->isDwl()) {

            if (!empty($object->getDwlId())) {
                $dwl = $this->getDWLRepository()->find($object->getDwlId());
                $visitor->setData(
                    'dwl',
                    ['id' => $dwl->getId(), 'date' => $dwl->getDate()->format('Y-m-d')]
                );
            }
        }
    }

    private function getDWLRepository(): DWLRepository
    {
        return $this->entityManager->getRepository(\DbBundle\Entity\DWL::class);
    }

    private function getGroupsFor($groups, Context $navigatorContext)
    {
        $paths = $navigatorContext->getCurrentPath();
        foreach ($paths as $index => $path) {
            if (!array_key_exists($path, $groups)) {
                if ($index > 0) {
                    $groups = array('Default');
                }

                break;
            }

            $groups = $groups[$path];
        }

        return $groups;
    }
}
