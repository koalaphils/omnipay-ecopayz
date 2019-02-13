<?php

namespace DbBundle\Entity\Interfaces;

interface PreservesOriginalInterface
{
	public function getOriginal();
	public function preserveOriginal(): void;
}
