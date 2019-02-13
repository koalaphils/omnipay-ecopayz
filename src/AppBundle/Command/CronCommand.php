<?php

namespace AppBundle\Command;

use Cron\Cron;
use Cron\Executor\Executor;
use Cron\Job\ShellJob;
use Cron\Schedule\CrontabSchedule;
use Cron\Resolver\ArrayResolver;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use DbBundle\Entity\User;
use DbBundle\Entity\Currency;
use Symfony\Component\Yaml\Yaml;

class CronCommand extends ContainerAwareCommand
{
    private $cronResolver;
    private $cron;

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->cronResolver = new ArrayResolver();
        $this->cron = new Cron();
        $this->cron->setExecutor(new Executor());
        $this->cron->setResolver($this->cronResolver);
    }

    protected function configure()
    {
        $this
            ->setName('scheduler:run')
            ->setDescription('a replacement for cron, instead of having many cron jobs, just put this console command as the "front controller" and let this console command handle the scheduling')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobs = $this->getJobs();
        foreach ($jobs as $cronJob) {
            $this->addConsoleCommandToCron($cronJob['schedule'], $cronJob['console-command']);
        }

        $this->cron->run();
    }

    /**
     *
     * sample usage $this->addConsoleCommandToCron('* * * * *', 'transaction:decline')
     *
     * @param string $cronSchedule you may use tools like https://crontab.guru to create your cron schedule
     * @param string $consoleCommand
     */
    private function addConsoleCommandToCron(string $cronSchedule, string $consoleCommand)
    {
        $cronJob = new ShellJob();
        $cronJob->setCommand('cd '. $this->getContainer()->get('kernel')->getRootDir() . ' && php console ' . $consoleCommand);
        $cronJob->setSchedule(new CrontabSchedule($cronSchedule));

        $this->cronResolver->addJob($cronJob);
    }

    private function getJobs(): array
    {
        $configFilepath = $this->getContainer()->get('kernel')->getRootDir() .'/config/cron.yml';

        return Yaml::parse(file_get_contents($configFilepath)) ?? [];
    }

}
