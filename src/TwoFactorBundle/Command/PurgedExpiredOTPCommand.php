<?php

namespace TwoFactorBundle\Command;

use DbBundle\Entity\TwoFactorCode;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TwoFactorBundle\Manager\TwoFactorManager;

class PurgedExpiredOTPCommand extends ContainerAwareCommand
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    protected function configure()
    {
        $this
            ->setName('two-factor:code:cleanup')
            ->setDescription('Remove Expired Two Factor Code')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->container->get('doctrine')->getManager()
            ->getRepository(TwoFactorCode::class)
            ->purgeExpiredIds()
        ;
    }
}
