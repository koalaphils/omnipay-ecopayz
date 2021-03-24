<?php

declare(strict_types = 1);

namespace AppBundle\Manager;

use Symfony\Component\Intl\Intl;

class AppManager
{
    /**
     * @var SettingManager
     */
    private $settingManager;

    public function __construct(SettingManager $settingManager)
    {
        $this->settingManager = $settingManager;
    }

    public function getAvailableLocales(): array
    {
        $localeLists = $this->settingManager->getSetting('locale.list');
        $locales = [];
        foreach ($localeLists as $locale) {
            $locales[$locale] = ['name' => Intl::getLocaleBundle()->getLocaleName($locale, true), 'code' => $locale];
        }

        return $locales;
    }
}