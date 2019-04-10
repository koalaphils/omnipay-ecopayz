<?php

namespace WebSocketBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PublishCommand extends ContainerAwareCommand
{
    public const COMMAND_NAME = 'websocket:publish';

    protected static $defaultName = PublishCommand::COMMAND_NAME;

    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->addArgument('topic', InputArgument::REQUIRED)
            ->addArgument('payload', InputArgument::REQUIRED)
            ->setDescription('Publish data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $publisher = $this->getContainer()->get('app.publisher');
        $publisher->publishUsingWamp($input->getArgument('topic'), json_decode($input->getArgument('payload'), true));
    }
}