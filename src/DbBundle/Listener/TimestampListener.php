<?php

namespace DbBundle\Listener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

/**
 * @author Cydrick Nonog <cydrick.dev@gmail.com>
 */
class TimestampListener
{
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();
        if ($entity instanceof \DbBundle\Entity\Interfaces\TimestampInterface) {
            $entity->setCreatedAt(new \DateTime());
        }

        if ($entity instanceof \DbBundle\Entity\Interfaces\VersionInterface
            && $entity->getVersionType() === 'datetime' && $entity->getVersionColumn() === 'updatedAt'
        ) {
            $entity->setUpdatedAt(new \DateTime());
        }
    }

    public function preUpdate(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getObject();
        if ($entity instanceof \DbBundle\Entity\Interfaces\TimestampInterface
            && (!($entity instanceof \DbBundle\Entity\Interfaces\VersionInterface)
            || ($entity instanceof \DbBundle\Entity\Interfaces\VersionInterface
                && $entity->getVersionColumn() !== 'updatedAt'))
        ) {
            $entity->setUpdatedAt(new \DateTime());
        }
    }
}
