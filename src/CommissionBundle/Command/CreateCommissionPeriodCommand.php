<?php

namespace CommissionBundle\Command;

use CommissionBundle\Service\CommissionService;
use DateTime;
use DbBundle\Entity\CommissionPeriod;
use DbBundle\Repository\CommissionPeriodRepository;
use JMS\JobQueueBundle\Console\CronCommand;
use JMS\JobQueueBundle\Entity\Job;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommissionPeriodCommand extends AbstractCommand implements CronCommand
{
    protected function configure()
    {
        $this
            ->setName('commission:period:create')
            ->setDescription('Create or Update a commission period depends on the settings')
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        
        $this->getCommissionService()->createOrUpdateCommissionPeriod(new \DateTimeImmutable('now'));
    }
    
    private function getCommissionService(): CommissionService
    {
        return $this->getContainer()->get('commission.service');
    }

    public function createCronJob(DateTime $lastRunAt)
    {
        return new Job('commission:period:create', [
            $this->getContainer()->getParameter('cron_user'),
            '--env',
            $this->getContainer()->get('kernel')->getEnvironment()
        ]);
    }

    public function shouldBeScheduled(DateTime $lastRunAt): bool
    {
        $lastPeriod = $this->getCommissionPeriodRepository()->getLastCommissionPeriod();
        if ($lastPeriod instanceof CommissionPeriod) {
            return new \DateTime('now') >= $lastPeriod->getDWLDateTo();
        }
        
        return false;
    }
    
    private function getCommissionPeriodRepository(): CommissionPeriodRepository
    {
        return $this
            ->getContainer()
            ->get('doctrine')
            ->getManagerForClass(CommissionPeriod::class)
            ->getRepository(CommissionPeriod::class);
    }
}
