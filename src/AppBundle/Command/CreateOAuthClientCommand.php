<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;

class CreateOAuthClientCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oauth:client:create')
            ->setDescription('Create OAuth Client')
            ->addArgument(
                'grantTypes',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Grant Type?'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clientManager = $this->getContainer()->get('fos_oauth_server.client_manager.default');
        $client = $clientManager->createClient();

        $grantTypes = $input->getArgument('grantTypes');

        $client->setAllowedGrantTypes($grantTypes);
        $clientManager->updateClient($client);
        $client->setRedirectUris([]);

        $output->writeln(sprintf("Client ID: %s", $client->getPublicId()));
        $output->writeln(sprintf("Client Secret: %s", $client->getSecret()));
    }
}
