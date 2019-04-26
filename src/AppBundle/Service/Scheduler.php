<?php

declare(strict_types = 1);

namespace AppBundle\Service;

use JMS\JobQueueBundle\Cron\JobScheduler;
use JMS\JobQueueBundle\Entity\Job;

class Scheduler implements JobScheduler
{
    public function createJob($command, \DateTime $lastRunAt)
    {
        $args = explode(' ', $command);
        $commandName = array_shift($args);

        return new Job($commandName, $args);
    }

    public function shouldSchedule($command, \DateTime $lastRunAt)
    {
        return time() - $lastRunAt->getTimestamp() >= (60*60);
    }
}