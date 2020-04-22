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
use Symfony\Component\Yaml\Yaml;

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
        $jwtGenerator = $this->getContainer()->get(JWTGeneratorService::class);
        $pinnacleIntegration = $factory->getIntegration('pinbet');

        $configPath = $this->getContainer()->getParameter('kernel.root_dir') . '/../var/config/pinnacle.yaml';
        $output->writeln('Parsing configuration file: ' . $configPath);
        $value = Yaml::parse(file_get_contents($configPath));
        $payload = $this->normalizeConfiguration($value['configuration']);

        $output->writeln('Sending configuration to Pinnacle Service');
        $pinnacleIntegration->configure($jwtGenerator->generate([]), $payload);
        $output->writeln('Done.');
    }

    // Normalize values to  API Accepted Payload Format
    private function normalizeConfiguration(array $config): array
    {
        $payload = [
            'items' => []
        ];

        foreach ($config as $key => $value) {
            $payload['items'][] = [
                'name'  => $config[$key]['name'],
                'limit' => $config[$key]['limit'],
                'events' => $config[$key]['events']
            ];
        }

        return $payload;
    }
}
