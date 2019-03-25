<?php

namespace PinnacleBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class PinnacleBundle extends Bundle
{
    public function registerDefaultSetting()
    {
        return [
            'pinnacle' => [],
        ];
    }

    public function registerSettingCodes()
    {
        return ['pinnacle'];
    }
}
