<?php

namespace DbBundle\Entity;

/**
 * Setting.
 */
class Setting extends Entity
{
    const SCHEDULER_TASK = 'task';
    const TASK_AUTODECLINE = 'auto_decline';
    const ENABLE_AUTO_DECLINE = false;
    const SCHEDULER_DEFAULT_MIN = 20;
    const TIME_DURATION_NAME = 'minutes';
    const ENABLE_AUTO_LOCK = false;
    const LOCKDOWN_PERIOD_MIN = 20;
    /**
     * @var string
     */
    private $code;

    /**
     * @var json
     */
    private $value;

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return Setting
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set value.
     *
     * @param json $value
     *
     * @return Setting
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value.
     *
     * @return json
     */
    public function getValue()
    {
        return $this->value;
    }
}
