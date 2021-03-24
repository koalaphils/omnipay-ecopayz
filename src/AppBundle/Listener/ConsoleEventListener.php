<?php

declare(strict_types = 1);

namespace AppBundle\Listener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleEventListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $timezone;

    public static function getSubscribedEvents()
    {
        return [
            'console.command' => 'onConsole',
        ];
    }

    public function __construct(string $timezone)
    {
        $this->timezone = $timezone;
    }

    public function onConsole(ConsoleCommandEvent $event): void
    {
        date_default_timezone_set($this->timezone);
    }
}