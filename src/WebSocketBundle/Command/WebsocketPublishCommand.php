<?php

namespace WebSocketBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebsocketPublishCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('websocket:publish')
            ->setDescription('...')
            ->addArgument('topic', InputArgument::REQUIRED, 'Topic where it will publish')
            ->addArgument('data', InputArgument::REQUIRED, 'Data to be send')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $client \Voryx\ThruwayBundle\Client\ClientManager */
        $client = $this->getContainer()->get('thruway.client');

        $data = json_decode($input->getArgument('data', false), true);

        $client->publish($input->getArgument('topic'), $data)->then(function () use ($client) {
            $client = null;
        });
    }
}
