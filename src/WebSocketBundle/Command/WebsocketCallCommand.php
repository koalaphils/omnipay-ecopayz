<?php

namespace WebSocketBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebsocketCallCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('websocket:call')
            ->setDescription('...')
            ->addArgument('procedure', InputArgument::REQUIRED, 'Procedure to be called')
            ->addArgument('data', InputArgument::REQUIRED, 'Data to be send')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /* @var $client \Voryx\ThruwayBundle\Client\ClientManager */
        $client = $this->getContainer()->get('thruway.client');

        $data = json_decode($input->getArgument('data', false), true);

        $client->call($input->getArgument('procedure'), $data)->then(function ($details) use ($client) {
            $client = null;
        });
    }
}
