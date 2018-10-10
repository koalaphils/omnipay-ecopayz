<?php

namespace AppBundle\Console;

use AppBundle\DoctrineExtension\DBAL\Connection;
use JMS\JobQueueBundle\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends BaseApplication
{
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->reconnectToDatabase();
        parent::doRun($input, $output);
    }
    
    public function onTick(): void
    {
        $this->reconnectToDatabase();
        parent::onTick();
    }
    
    private function getConnection(): Connection
    {
        return $this->getKernel()->getContainer()->get('doctrine')->getManagerForClass('JMSJobQueueBundle:Job')->getConnection();
    }
    
    private function reconnectToDatabase(): void
    {
        $this->getConnection()->reconnect();
    }
}
