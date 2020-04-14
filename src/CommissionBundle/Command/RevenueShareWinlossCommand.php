<?php

namespace CommissionBundle\Command;

use DateTime;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class RevenueShareWinlossCommand extends AbstractCommand
{
    public const COMMAND_NAME = 'revenueshare:period:winloss';
    private const OPTION_PERIOD = 'period';
    private const OPTION_WINLOSS = 'winloss';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Retrieve daily winloss for periods')
            ->addOption(
                self::OPTION_PERIOD,
                'p',
                InputOption::VALUE_REQUIRED,
                'Period ID', 'latest'
            )
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(0);
        parent::execute($input, $output);

        $period = $input->getOption(self::OPTION_PERIOD);
        $response = $this->loginApiGateway();
        if ($response) {
            $token = $this->getToken($response);

            if ($token) {
                $payoutResponse = $this->updatePeriod($period, $token, self::OPTION_WINLOSS);
                $output->write($payoutResponse);
            }
        }
    }
}
