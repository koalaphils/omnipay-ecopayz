<?php

namespace ProductIntegrationBundle\Command;

use ProductIntegrationBundle\ProductIntegrationFactory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class PinnacleConfigCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('integration:configure-pinnacle')
            ->setDescription('Configure Pinnacle settings')
            ->setHelp('This command calls Pinnacle Microservice to configure Pinnacle Hot Events responses such as sorting and limit.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory =  $this->getContainer()->get(ProductIntegrationFactory::class);
        $pinnacleIntegration = $factory->getIntegration('pinbet');
        $pinnacleIntegration->configure();
        // $tokenStorage = $this->getContainer()->get('security.token_storage');
        // $authenticationManager = $this->getContainer()->get('security.authentication.manager');
        
        // $unauthenticatedToken = new UsernamePasswordToken(
        //     'admin',
        //     'admin',
        //     'main'
        // );

        // $authenticatedToken = $authenticationManager->authenticate($unauthenticatedToken);
       

        $output->writeln('MEOW');
        return 0;
    }
}
