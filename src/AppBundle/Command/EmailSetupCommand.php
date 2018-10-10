<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EmailSetupCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:email-setup')
            ->setDescription('Copy and moves the email templates to uploads folder.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        $origEmailPath = $container->getParameter('kernel.root_dir') . '/../var/emails';
        $destEmailPath = $container->getParameter('upload_folder') . '/emails';

        if (is_dir($destEmailPath)) {
            unlink($destEmailPath);
        }

        symlink($origEmailPath, $destEmailPath);
    }
}