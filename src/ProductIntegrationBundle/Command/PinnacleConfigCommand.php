<?php

namespace ProductIntegrationBundle\Command;

use ApiBundle\Service\JWTGeneratorService;
use ProductIntegrationBundle\ProductIntegrationFactory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Console\Input\InputOption;

class PinnacleConfigCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('integration:configure-pinnacle')
            ->setDescription('Configure Pinnacle settings')
            ->setHelp('This command calls Pinnacle Microservice to configure Pinnacle Hot Events responses such as sorting and limit.')
            ->addOption(
                'config',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factory =  $this->getContainer()->get(ProductIntegrationFactory::class);
        $jwtGenerator = $this->getContainer()->get(JWTGeneratorService::class);
        $pinnacleIntegration = $factory->getIntegration('pinbet');
        $pinnacleIntegration->configure($jwtGenerator->generate([]));
        $config = $input->getOption('config');
        dump(json_decode($config[0]));

        $output->writeln('MEOW');
        
        return 0;
    }
}
