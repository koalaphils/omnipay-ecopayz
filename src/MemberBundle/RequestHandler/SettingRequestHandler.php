<?php

declare(strict_types = 1);

namespace MemberBundle\RequestHandler;

use AppBundle\Manager\SettingManager;
use MemberBundle\Request\ReferralSettingRequest;

class SettingRequestHandler
{
    /**
     * @var SettingManager
     */
    private $settingManager;

    public function __construct(SettingManager $settingManager)
    {
        $this->settingManager = $settingManager;
    }

    public function handleReferralSetting(ReferralSettingRequest $referralSettingRequest): void
    {
        $expiration = $referralSettingRequest->getCookieExpiration() * 86400;
        $this->settingManager->updateSetting('referral', [
            'cookie' => [
                'expiration' => $expiration,
                'unit' => 'seconds'
            ]
        ]);
    }
}