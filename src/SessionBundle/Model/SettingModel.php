<?php

declare(strict_types = 1);

namespace SessionBundle\Model;

class SettingModel
{
    /**
     * @var int
     */
    private $sessionTimeout;

    /**
     * @var int
     */
    private $pinnacleTimeout;

    public static function fromArray(array $data): self
    {
        $instance = new SettingModel();
        $instance->setSessionTimeout($data['timeout']);
        $instance->setPinnacleTimeout($data['pinnacle_timeout']);

        return $instance;
    }

    public function getSessionTimeout(): int
    {
        return $this->sessionTimeout;
    }

    public function setSessionTimeout(int $timeout): self
    {
        $this->sessionTimeout = $timeout;

        return $this;
    }

    public function getPinnacleTimeout(): int
    {
        return $this->pinnacleTimeout;
    }

    public function setPinnacleTimeout(int $timeout): self
    {
        $this->pinnacleTimeout = $timeout;

        return $this;
    }
}