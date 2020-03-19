<?php

namespace ProductIntegrationBundle\Integration;

interface PinnaclePlayerInterface
{
    public function create(): array;
    public function view(string $userCode);
}