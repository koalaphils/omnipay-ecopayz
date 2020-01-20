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
        $queue = Job::DEFAULT_QUEUE;

        if ($commandName === 'transaction:decline') {
            $queue = 'autoDecline';
        }

        if ($commandName === 'revenueshare:period:compute') {
            $queue = 'compute';
        }

        return new Job($commandName, $args, true, $queue);
    }

    public function shouldSchedule($command, \DateTime $lastRunAt)
    {
        $args = explode(' ', $command);
        $commandName = $args[0];
        if ($commandName === 'fos:oauth-server:clean') {
            return time() - $lastRunAt->getTimestamp() >= 86400;
        }

        if ($commandName === 'transaction:decline') {
            return time() - $lastRunAt->getTimestamp() >= 60;
        }

        if ($commandName === 'revenueshare:period:compute') {
            return time() - $lastRunAt->getTimestamp() >= 86400;
        }

        return time() - $lastRunAt->getTimestamp() >= (60*60);
    }
}