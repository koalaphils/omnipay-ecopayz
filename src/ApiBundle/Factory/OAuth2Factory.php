<?php

declare(strict_types = 1);

namespace ApiBundle\Factory;

use AppBundle\Manager\SettingManager;
use OAuth2\IOAuth2Storage;
use OAuth2\OAuth2;

class OAuth2Factory
{
    /**
     * @var SettingManager
     */
    private $settingManager;

    /**
     * @var IOAuth2Storage
     */
    private $storage;

    /**
     * @var array
     */
    private $configs;

    public function __construct(SettingManager $settingManager)
    {
        $this->settingManager = $settingManager;
    }

    public function generate(IOAuth2Storage $storage, array $configs = []): OAuth2
    {
        $configs['access_token_lifetime'] = $this->settingManager->getSetting('session.timeout', OAuth2::DEFAULT_ACCESS_TOKEN_LIFETIME);
        $oauth2 = new OAuth2($storage, $configs);

        return $oauth2;
    }
}