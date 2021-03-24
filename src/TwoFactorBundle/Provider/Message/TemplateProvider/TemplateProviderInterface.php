<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message\TemplateProvider;

interface TemplateProviderInterface
{
    public function getTemplateInfo(string $template): array;

    public function hasTemplate(string $template): bool;
}