<?php

namespace MemberRequestBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use DbBundle\Entity\User;
use MemberRequestBundle\Service\MemberRequestService;

class MemberRequestProductPasswordMigrationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('memberRequest:productPassword-migration')
            ->setDescription('Migrate reset product password data from transaction to memberRequest table')
            ->setHelp('This will seggregate transaction to member request - reset product password')
            ->addArgument('username', InputArgument::REQUIRED, 'user username')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);
        try {
            $memberRequestService = $this->getMemberRequestService();
            $memberRequestService->createHttpRequest();
            $user = $memberRequestService->loginUserByUsername($input->getArgument('username'));

            if ($user instanceof User) {
                $memberRequestService->setLoggerForUser($user, $logger);
                $memberRequestService->setMemberRequestLogger($logger);
                $memberRequestService->processMigrationForProductPassword();
            }
        } catch (\Exception $e) {
            $logger->error(sprintf("%s\n%s:%s\n%s", $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()), [
                'class' => get_class($e),
            ]);
        }

        $runTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 4);
        $logger->info(sprintf('Runtime: %s ms', $runTime));
    }

    private function getMemberRequestService(): MemberRequestService
    {
        return $this->getContainer()->get('member_request.service');
    }
}