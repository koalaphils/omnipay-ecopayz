<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReferralToolsSetupCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:referral-tools-setup')
            ->setDescription('Copy and moves the tracking html code file to uploads folder.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $origEmailPath = $container->getParameter('kernel.root_dir') . '/../var/referralTools';
        $destEmailPath = $container->getParameter('upload_folder') . '/referralTools';

        if (is_dir($destEmailPath)) {
            unlink($destEmailPath);
        }

        symlink($origEmailPath, $destEmailPath);
    }
}