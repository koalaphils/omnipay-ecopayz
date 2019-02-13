<?php

namespace DbBundle\Utils;

use CI\InventoryBundle\Entity\PreservesOriginalInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\ORM\EntityManager;
use Symfony\Component\PropertyAccess\PropertyAccess;

// TODO: Support Doctrine Collections
class CollectionUtils
{
	/**
	 * Returns the items present in $old but not in $new.
	 * This is often used to find the items that were removed
	 * after the variable referencing $old was changed to $new.
	 *
	 * NOTE: This implementation will only work for entities.
	 */
	public static function diff($old, $new): array
	{
		// Optimization for the common case where they are the same,
		// and hence have no difference.
		if ($old === $new) {
			return [];
		}

		$inNew = []; // the "set" of IDs in $new
		foreach ($new as $entity) {
			$inNew[$entity->getId()] = true;
		}

		return array_filter($old, function($entity) use ($inNew) {
			return !isset($inNew[$entity->getId()]);
		});
	}

	/**
	 * A shortcut for getting the items removed in an entity that has preserved
	 * its original state.
	 */
	public static function getRemovedItems($entity, string $property = 'items'): array
	{
		$accessor = PropertyAccess::createPropertyAccessor();

		$originalItems = $accessor->getValue($entity->getOriginal(), $property);

		$currentItems = $accessor->getValue($entity, $property);

		return self::diff($originalItems, $currentItems);
	}
}
