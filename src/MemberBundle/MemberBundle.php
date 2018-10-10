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
            ]
        ];
    }
}
