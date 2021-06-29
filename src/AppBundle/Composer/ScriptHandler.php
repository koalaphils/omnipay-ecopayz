<?php

namespace AppBundle\Composer;

use Composer\Script\Event;

class ScriptHandler extends \Sensio\Bundle\DistributionBundle\Composer\ScriptHandler
{
    public static function setupEmailDirectory(Event $event)
    {
        $consoleDir = self::getConsoleDir($event, 'email setup');

        if (null === $consoleDir) {
            return;
        }

        static::executeCommand($event, $consoleDir, 'app:email-setup');
    }

    public static function setupReferralToolsDirectory(Event $event)
    {
        $consoleDir = self::getConsoleDir($event, 'referral tools setup');

        if (null === $consoleDir) {
            return;
        }

        static::executeCommand($event, $consoleDir, 'app:referral-tools-setup');
    }
}