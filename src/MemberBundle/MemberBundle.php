<?php

namespace MemberBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MemberBundle extends Bundle
{
    public function registerDefaultSetting()
    {
        return [
            'member' => [
                'referralName' => [
                    'max' => 50
                ],
                'website' => [
                    'max' => 50
                ],
                'locale' => ['default' => 'en']
            ],
            'referral' => [
                'cookie' => [
                    'expiration' => 2592000,
                    'unit' => 'seconds'
                ]
            ],
            'registration' => [
                'mail' => [
                    'subject' => 'New customer Signup: {{ from }}',
                    'lead_subject' => 'New lead: {{ from }}',
                    'to' => 'support@piwi247.com'
                ]
            ]
        ];
    }

    public function registerSettingCodes(): array
    {
        return ['referral'];
    }
}
