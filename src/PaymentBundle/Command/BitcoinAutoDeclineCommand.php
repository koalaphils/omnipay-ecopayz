<?php

namespace PaymentBundle\Command;

use AppBundle\Manager\SettingManager;
use JMS\JobQueueBundle\Console\CronCommand;
use JMS\JobQueueBundle\Entity\Job;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use DbBundle\Entity\User;
use TransactionBundle\Service\TransactionDeclineService;

class BitcoinAutoDeclineCommand extends ContainerAwareCommand implements CronCommand
{
    public function createCronJob(\DateTime $lastRunAt)
    {
        $cronUser = $this->getContainer()->getParameter('cron_user');

        return new Job($this->getName(), [$cronUser]);
    }

    public function shouldBeScheduled(\DateTime $lastRunAt)
    {
        /* @var $settingManager SettingManager */
        $settingManager = $this->getContainer()->get(SettingManager::class);
        if ($settingManager->getSetting('bitcoin.setting.configuration.autoDecline')) {
            $interval = $settingManager->getSetting('bitcoin.setting.configuration.minutesInterval') * 60;

            return time() - $lastRunAt->getTimestamp() >= $interval;
        }

        return false;
    }

    protected function configure()
    {
        $this
            ->setName('transaction:pending-bitcoin:decline')
            ->setDescription('Decline Pending Bitcoin Transactions')
            ->setHelp('This will help the CS to decline bitcoin transaction automatically due to no deposit received in payment gateway and based on lock time/duration')
            ->addArgument('username', InputArgument::REQUIRED, 'user username');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);
        try {
            $bitcoinAutoDeclineService = $this->getBitcoinAutoDeclineService();
            $bitcoinAutoDeclineService->createHttpRequest();
            $user = $bitcoinAutoDeclineService->loginUserByUsername($input->getArgument('username'));
            
            if ($user instanceof User) {
                $bitcoinAutoDeclineService->setLoggerForUser($user, $logger);
            }

            if ($bitcoinAutoDeclineService->getBitcoinAutoDeclineStatus()) {
                $bitcoinAutoDeclineService->setAutoDeclineLogger($logger);
                $bitcoinAutoDeclineService->declineBitcoinTransactions();
            } else {
                throw new \Exception('Bitcoin auto decline service was not active.');
            }
        } catch (\Exception $e) {
            $logger->error(sprintf("%s\n%s:%s\n%s", $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()), [
                'class' => get_class($e),
            ]);
        }

        $runTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 4);
        $logger->info(sprintf('Runtime: %s ms', $runTime));
    }
    
    private function getBitcoinAutoDeclineService(): TransactionDeclineService
    {
        return $this->getContainer()->get('transaction.decline.service');
    }
}