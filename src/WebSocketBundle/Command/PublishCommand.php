<?php

namespace WebSocketBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PublishCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('socket:publish')
            ->setDescription('...')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $publisher = $this->getContainer()->get('app.publisher');
        $publisher->publish('ms.topic.transaction_deposit', 'Sample publish');
    }
}