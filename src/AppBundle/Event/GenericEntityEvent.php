<?php

namespace AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class GenericEntityEvent extends Event
{
	private $entity;

	public function __construct($entity)
	{
		$this->entity = $entity;
	}

	public function getEntity()
	{
		return $this->entity;
	}
}