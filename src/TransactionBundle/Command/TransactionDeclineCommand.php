<?php

namespace TransactionBundle\Command;

use JMS\JobQueueBundle\Console\CronCommand;
use JMS\JobQueueBundle\Console\ScheduleEveryMinute;
use JMS\JobQueueBundle\Console\ScheduleInSecondInterval;
use JMS\JobQueueBundle\Entity\Job;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use DbBundle\Entity\User;
use TransactionBundle\Service\TransactionDeclineService;

class TransactionDeclineCommand extends ContainerAwareCommand implements CronCommand
{
    use ScheduleEveryMinute;

    public function createCronJob(\DateTime $dateTime)
    {
        return new Job($this->getName(), [$this->getContainer()->getParameter('cron_user')]);
    }

    protected function configure()
    {
        $this
            ->setName('transaction:decline')
            ->setDescription('Decline Transaction')
            ->setHelp('This will help the CS to decline transaction automatically due to no deposit received in payment gateway')
            ->addArgument('username', InputArgument::REQUIRED, 'user username')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);
        try {
            $transactionAutoDeclineService = $this->getTransactionAutoDeclineService();
            $transactionAutoDeclineService->createHttpRequest();
            $user = $transactionAutoDeclineService->loginUserByUsername($input->getArgument('username'));
            
            if ($user instanceof User) {
                $transactionAutoDeclineService->setLoggerForUser($user, $logger);
            }

            if ($transactionAutoDeclineService->getAutoDeclineStatus()) {
                $transactionAutoDeclineService->setAutoDeclineLogger($logger);
                $transactionAutoDeclineService->declineTransactions();
            } else {
                throw new \Exception('Auto decline service was not active.');
            }
        } catch (\Exception $e) {
            $logger->error(sprintf("%s\n%s:%s\n%s", $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()), [
                'class' => get_class($e),
            ]);
        }

        $runTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 4);
        $logger->info(sprintf('Runtime: %s ms', $runTime));
    }

    protected function executes(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);

        try {
            $this->createRequest();
            $user = $this->loginUser($input->getArgument('username'));
            $roles = $user->getRoles();

            if (!in_array('role.scheduler', $roles)) {
                throw new \Exception('Access Denied.');
            }
            $logger->info(sprintf('Login User: %s [%s]', $user->getUsername(), $user->getId()));

            $service = $this->getTransactionDeclineService();

            $isReadyToDecline = $service->getAutoDeclineStatus();
            if (!$isReadyToDecline) {
                throw new \Exception('Task scheduler not active.');
            }

            $service->setLogger($logger);
            $returnedResult = $service->processDeclining();
            if (!empty($returnedResult['result'])) {
                $logger->info('Declined Ids: ' . json_encode($returnedResult['result']));
            } else {
                $logger->info('Nothing to decline');
            }
        } catch (\Exception $e) {
            $logger->error($this->getErrorMessage($e), [
                'class' => get_class($e),
            ]);
        }

        $runTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 4);
        $logger->info(sprintf('Runtime: %s ms', $runTime));
    }

    private function getTransactionAutoDeclineService(): TransactionDeclineService
    {
        return $this->getContainer()->get('transaction.decline.service');
    }
}
