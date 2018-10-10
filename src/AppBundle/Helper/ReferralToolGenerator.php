<?php

namespace AppBundle\Helper;

use AppBundle\Manager\SettingManager;
use MediaBundle\Manager\MediaManager;
use Symfony\Component\Templating\EngineInterface;

class ReferralToolGenerator
{
    private $settingManager;
    private $mediaManager;
    private $twigEngine;

    public function __construct(SettingManager $settingManager, MediaManager $mediaManager, EngineInterface $twigEngine)
    {
        $this->settingManager = $settingManager;
        $this->mediaManager = $mediaManager;
        $this->twigEngine = $twigEngine;
    }

    public function generateReferralLink(array $options): string
    {
        $referralToolsSetting = $this->getReferralToolsSetting();

        return sprintf(
            '%s?%s=%s',
            $referralToolsSetting[sprintf('links.%s.urls.%s', $options['language'], $options['type'])],
            $referralToolsSetting['piwikUrlKey'],
            $options['trackingCode']
        );
    }

    public function generateTrackingHtmlCode(array $options): string
    {
        $referralToolsSetting = $this->getReferralToolsSetting();
        $options['siteId'] = $referralToolsSetting[sprintf('links.%s.siteId', $options['language'])];

        return $this->twigEngine->render($this->getTrackerFile()->getRealPath(), $options);
    }

    private function getTrackerFile(): \Symfony\Component\HttpFoundation\File\File
    {
        return $this->mediaManager->getFile('referralTools' . DIRECTORY_SEPARATOR . 'tracker.html.twig');
    }

    private function getReferralToolsSetting(): array
    {
        return $this->settingManager->flattenSetting('referral.tools');
    }
}