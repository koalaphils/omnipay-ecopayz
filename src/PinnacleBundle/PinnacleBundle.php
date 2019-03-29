<?php

namespace PinnacleBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PinnacleBundle extends Bundle
{
    public function registerDefaultSetting()
    {
        return [
            'pinnacle' => ['product' => ''],
        ];
    }

    public function registerSettingCodes()
    {
        return ['pinnacle'];
    }
}
