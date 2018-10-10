<?php

namespace DbBundle\Listener;

use AppBundle\Event\GenericEntityEvent;
use CustomerBundle\Events;
use DateTime;
use DbBundle\Entity\Interfaces\VersionableInterface;
use DbBundle\Utils\VersionableUtils;
use Doctrine\ORM\EntityManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class VersionableListener implements EventSubscriberInterface
{
    const VERSIONABLE_SAVE = 'bo.event.versionable_save';

    private $em;

    public function __construct(EntityManager $em)
	{
		$this->em = $em;
    }

    public static function getSubscribedEvents()
    {
        return [
            static::VERSIONABLE_SAVE => 'onSave',
            Events::RISK_SETTING_SAVE => 'onSave',
        ];
    }

    public function onSave(GenericEntityEvent $event)
    {
        $entity = $event->getEntity();
        if (!$entity instanceof VersionableInterface) {
            return;
        }

        $original = $entity->getOriginal();

        if (!$original->hasBeenPersisted()) {
            $entity->setResourceId($entity->generateResourceId());
            if (!($entity->getCreatedAt() instanceof DateTime)) {
                $entity->setCreatedAt(new DateTime());
            }
            $entity->setToLatest();
            $this->em->persist($entity);
            $this->em->flush($entity);
        } else {
            $this->createNewVersion($entity);
            $this->em->persist($entity);
            $this->em->flush($entity);
        }
    }

    protected function createNewVersion(VersionableInterface $versionableEntity)
    {
        $newVersionEntity = VersionableUtils::clone($versionableEntity);
        VersionableUtils::revertToOriginal($versionableEntity, $this->em);
        $versionableEntity->makeItHistory();
        $newVersionEntity->setToLatest();
        $newVersionEntity->incrementVersion();
        $newVersionEntity->setCreatedAt(new DateTime());

        $this->em->persist($newVersionEntity);
        $this->em->flush($newVersionEntity);
    }
}