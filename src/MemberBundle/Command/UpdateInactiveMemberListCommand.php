<?php

namespace MemberBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateInactiveMemberListCommand extends ContainerAwareCommand
{
    public const COMMAND_NAME = 'member:inactive-list:update';

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('truncates the current inactive member list and replaces it with member Ids that have been found to be (inactive) based on standing rules')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Updating...');
        $container = $this->getContainer();
        $logger = $container->get('logger');
        $inactiveMemberManager = $container->get('member.inactive.manager');
        $inactiveMembersCountBeforeUpdate = $inactiveMemberManager->getInactiveMembersCount();
        $inactiveMemberManager->updateInactiveList();
        $inactiveMembersCountAfterUpdate = $inactiveMemberManager->getInactiveMembersCount();

        $output->writeln([
            'Inactive Members Count Before Update: ' . $inactiveMembersCountBeforeUpdate,
            'Inactive Members Count After Update: ' . $inactiveMembersCountAfterUpdate,
        ]);

        $logger->info(sprintf('Updated Inactive Member List, before update:(%d), after update:(%d)', $inactiveMembersCountBeforeUpdate, $inactiveMembersCountAfterUpdate));

    }
}