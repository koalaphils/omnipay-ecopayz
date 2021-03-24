<?php

declare(strict_types = 1);

namespace PinnacleBundle\Component\Model;

class WinlossResponse
{
	private $total;
	private $report;

	public static function create(array $data): self
    {
        $instance = new static();
        $instance->total = $data['total'];
        $instance->report = $data['report'];

        return $instance;
    }

    public function getTotal(): array
    {
        return $this->total;
    }

    public function getReport(): array
    {
        return $this->report;
    }

    public function getTotalDetail($key, $default = null)
    {
        return array_get($this->getTotal(), $key, $default);
    }
}