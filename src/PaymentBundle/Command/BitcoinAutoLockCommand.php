<?php

namespace PaymentBundle\Command;

use AppBundle\Service\PaymentOptionService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use DbBundle\Entity\User;
use TransactionBundle\Service\TransactionBitcoinLockRateService;

class BitcoinAutoLockCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('transaction:pending-bitcoin:lock')
            ->setDescription('Lock Pending Bitcoin Transactions')
            ->setHelp('This will help the CS to lock down bitcoin transactions automatically due to lock time period expired')
            ->addArgument('username', InputArgument::REQUIRED, 'user username');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);
        try {
	        $settings = $this->getBitcoinPaymentOptionSettings();
	        if ($settings['autoLock'] === true)
			{
				$bitcoinAutoLockService = $this->getBitcoinAutoLockService();
				$bitcoinAutoLockService->createHTTPRequest();

				$user = $bitcoinAutoLockService->loginUserByUsername($input->getArgument('username'));

				if ($user instanceof User)
				{
					$bitcoinAutoLockService->setLoggerForUser($user, $logger);
				}

				$bitcoinAutoLockService->setAutoLockLogger($logger);
				$bitcoinAutoLockService->lockBitcoinTransactions($settings['lockDownPeriod']);
	        }
			else
			{
                throw new \Exception('Bitcoin auto lock service was not active.');
            }
        } catch (\Exception $e) {
            $logger->error(sprintf("%s\n%s:%s\n%s", $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()), [
                'class' => get_class($e),
            ]);
        }

        $runTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 4);
        $logger->info(sprintf('Runtime: %s ms', $runTime));
    }

	private function getBitcoinPaymentOptionSettings()
	{
		$bitcoinPaymentOption = $this->getPaymentOptionService()->getPaymentOption(PaymentOptionService::BITCOIN);

		return $bitcoinPaymentOption['settings']['provider'];
	}

	private function getPaymentOptionService(): PaymentOptionService
	{
		return $this->getContainer()->get('app.service.payment_option_service');
	}
    
    private function getBitcoinAutoLockService(): TransactionBitcoinLockRateService
    {
        return $this->getContainer()->get('transaction.lock.service');
    }
}