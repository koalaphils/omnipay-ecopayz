<?php

namespace CommissionBundle\Command;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use DbBundle\Repository\CommissionPeriodRepository;
use DbBundle\Entity\CommissionPeriod;

class RevenueShareComputeCommand extends AbstractCommand
{
    public const COMMAND_NAME = 'revenueshare:period:compute';
    private const OPTION_PERIOD = 'period';
    private const OPTION_RECOMPUTE = 'recompute';
    private const OPTION_COMPUTE = 'compute';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Compute the revenue share for periods that was already completed')
            ->addOption(
                self::OPTION_PERIOD,
                'p',
                InputOption::VALUE_REQUIRED,
                'Period ID', 'all'
            )
            ->addOption(
                self::OPTION_RECOMPUTE,
                'r',
                InputOption::VALUE_REQUIRED,
                "Force Recompute",
                '0'
            )
        ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(0);
        parent::execute($input, $output);

        $period = $input->getOption(self::OPTION_PERIOD);
        if ($period == "latest") {
            $latestPeriod = $this->getCommissionPeriodRepository()->getLastCommissionPeriod() ?? 0;
            $period = $latestPeriod->getIdentifier();
        }

        $recompute = $input->getOption(self::OPTION_RECOMPUTE);
        $type = $recompute ? self::OPTION_RECOMPUTE : self::OPTION_COMPUTE;

        $response = $this->loginApiGateway();
        $output->write($response);
        if ($response) {
            $token = $this->getToken($response);
            if ($token) {
                $computeResponse = $this->updatePeriod($period, $token, $type);
                $output->write($computeResponse);
            }
        }
    }

    private function getCommissionPeriodRepository(): CommissionPeriodRepository
    {
        return $this->getDoctrine()->getRepository(CommissionPeriod::class);
    }
}