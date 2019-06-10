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
            ],
            'referral' => [
                'cookie' => [
                    'expiration' => 2592000,
                    'unit' => 'seconds'
                ]
            ]
        ];
    }

    public function registerSettingCodes(): array
    {
        return ['referral'];
    }
}
