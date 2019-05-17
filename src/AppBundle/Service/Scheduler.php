<?php

declare(strict_types = 1);

namespace AppBundle\Service;

use JMS\JobQueueBundle\Console\ScheduleDaily;
use JMS\JobQueueBundle\Cron\JobScheduler;
use JMS\JobQueueBundle\Entity\Job;

class Scheduler implements JobScheduler
{
    use ScheduleDaily;

    public function createJob($command, \DateTime $lastRunAt)
    {
        $args = explode(' ', $command);
        $commandName = array_shift($args);

        return new Job($commandName, $args);
    }

    public function shouldSchedule($command, \DateTime $lastRunAt)
    {
        if ($command === 'fos:oauth-server:clean') {
            return time() - $lastRunAt->getTimestamp() >= 86400;
        }

        return time() - $lastRunAt->getTimestamp() >= (60*60);
    }
}