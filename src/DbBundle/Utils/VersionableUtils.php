<?php

namespace DbBundle\Utils;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\Inflector;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\PropertyAccess\PropertyAccess;

use DbBundle\Entity\Interfaces\VersionableInterface;

class VersionableUtils
{
    public static function preserveOriginal(VersionableInterface $entity): void
    {
        $original = clone $entity;

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $reflectionObject = new \ReflectionClass($entity);
        $classShortName = Inflector::camelize($reflectionObject->getShortName());
        $properties = $entity->getVersionedProperties();

        foreach ($properties as $prop) {
            $value = $propertyAccessor->getValue($entity, $prop);
            // We got different way of setting value  on collections.
            if ($value instanceof PersistentCollection) {
                $newValue = new ArrayCollection();

                foreach ($value as $child) {
                    $clonedChild = clone $child;
                    $propertyAccessor->setValue($clonedChild, $classShortName, $entity);
                    $newValue->add($clonedChild);
                }

                $propertyAccessor->setValue($original, $prop, $newValue);
            } else {
                $propertyAccessor->setValue($original, $prop, $value);
            }
        }

        $entity->setOriginal($original);
    }

    public static function revertToOriginal(VersionableInterface $entity, EntityManager $em)
    {
        $original = $entity->getOriginal();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        // Revert
        $properties = $entity->getVersionedProperties();

        foreach ($properties as $prop) {
            $value = $propertyAccessor->getValue($original, $prop);
            if ($value instanceof ArrayCollection) {
                $newValue = $propertyAccessor->getValue($entity,  $prop);

                // Remove new associations to avoid accidental persistence.
                foreach ($newValue as $child) {
                    $em->remove($child);
                }
                $propertyAccessor->setValue($entity, $prop, $value);
            } else {
                $propertyAccessor->setValue($entity, $prop, $value);
            }
        }
    }

    /**
     * Clones VersionableChild the way it should be used
     * by the Versionable implementation
     */
    public static function clone(VersionableInterface $entity): VersionableInterface
    {
        $cloned = clone $entity;
        $cloned->setId(null);
        $properties = $entity->getVersionedProperties();
        $reflectionObject = new \ReflectionClass($entity);
        $classShortName = Inflector::camelize($reflectionObject->getShortName());

        // Clone all children (if any)
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($properties as $prop) {
            $value = $propertyAccessor->getValue($entity, $prop);
            if ($value instanceof PersistentCollection) {
                $children = $value;
                $clonedChildren = new ArrayCollection();
                foreach ($children as $child) {
                    $clonedChild = clone $child;
                    $propertyAccessor->setValue($clonedChild, $classShortName, $cloned);
                    $clonedChildren->add($clonedChild);
                }
                $propertyAccessor->setValue($cloned, $prop, $clonedChildren);
            }
        }

        return $cloned;
    }
}