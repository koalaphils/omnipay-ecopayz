<?php

namespace SessionBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SessionBundle extends Bundle
{
    public function registerDefaultSetting(): array
    {
        return [
            'session' => [
                'timeout' => 3600,
                'pinnacle_timeout' => 600,
            ]
        ];
    }

    public function registerSettingCodes(): array
    {
        return ['session'];
    }
}
