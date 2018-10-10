<?php

namespace DbBundle\Entity\Interfaces;

interface VersionableInterface
{
    public function getVersion(): int;

    public function getResourceId(): ?string;

    public function getCreatedAt(): ?\DateTimeInterface;

    public function setVersion(int $version): self;

    public function setResourceId(string $resourceId): self;

    public function setCreatedAt(\DateTimeInterface $createdAt): self;

    public function incrementVersion(): int;

    public function generateResourceId(): string;

    public function isLatest(): bool;

    public function makeItHistory(): void;

    public function setToLatest(): void;

    public function getVersionedProperties(): array;
}
