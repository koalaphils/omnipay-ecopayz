<?php

namespace DbBundle\Entity\Traits;

trait PreservesOriginalTrait
{
	private $original;

	public function getOriginal()
	{
		if ($this->original === null && !$this->hasBeenPersisted()) {
			$this->original = new self();
		}

		return $this->original;
	}

	public function preserveOriginal(): void
	{
		$this->original = clone $this;
	}
}
