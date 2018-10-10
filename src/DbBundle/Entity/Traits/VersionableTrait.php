<?php

namespace DbBundle\Entity\Traits;

use DateTimeInterface;
use DbBundle\Entity\Interfaces\VersionableInterface;

trait VersionableTrait
{
    private $version = 1;
    private $createdAt;
    private $resourceId;
    private $isLatest;
    private $original;

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setVersion(int $version): VersionableInterface
    {
        $this->version = $version;

        return $this;
    }

    public function setResourceId(string $resourceId): VersionableInterface
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): VersionableInterface
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function incrementVersion(): int
    {
        $this->version += 1;

        return $this->version;
    }

    public function isLatest(): bool
    {
        return $this->isLatest;
    }

    public function makeItHistory(): void
    {
        $this->isLatest = false;
    }

    public function setToLatest(): void
    {
        $this->isLatest = true;
    }

    public function getResourceId(): ?string
    {
        return $this->resourceId;
    }

    public function preserveOriginal(): void
    {
        $this->original = clone $this;
    }

    public function setOriginal($original): void
    {
        $this->original = $original;
    }

    public function getOriginal(): self
    {
        return $this->original;
    }

    abstract public function generateResourceId(): string;
}
