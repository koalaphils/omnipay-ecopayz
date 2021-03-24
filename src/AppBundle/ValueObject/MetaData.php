<?php

namespace AppBundle\ValueObject;

class MetaData
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function get(string $key)
    {
        return array_get($this->data, $key);
    }

    public function set(string $key, $value): MetaData
    {
        $metaData = new MetaData($this->toArray());
        array_set($metaData->data, $key, $value);

        return $metaData;
    }

    public function remove(string $key): MetaData
    {
        $metaData = new MetaData($this->toArray());
        array_forget($metaData->data, [$key]);

        return $metaData;
    }

    public function has(string $key): bool
    {
        return array_has($this->data, $key);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
