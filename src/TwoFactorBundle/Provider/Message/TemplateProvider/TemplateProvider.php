<?php

declare(strict_types = 1);

namespace TwoFactorBundle\Provider\Message\TemplateProvider;

use AppBundle\Manager\SettingManager;

class TemplateProvider implements TemplateProviderInterface
{
    /**
     * @var array
     */
    private $templates;

    public function __construct(SettingManager $settingManager)
    {
        $this->templates = $settingManager->getSetting('email.templates', []);
    }

    public function getTemplateInfo(string $template): array
    {
        if ($this->hasTemplate($template)) {
            return array_get($this->templates, $template);
        }

        return array_get($this->templates, 'default');
    }

    public function hasTemplate(string $template): bool
    {
        return array_has($this->templates, $template);
    }
}