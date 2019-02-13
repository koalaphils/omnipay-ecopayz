<?php

namespace CommissionBundle\Command;

use CommissionBundle\Manager\CommissionManager;
use CommissionBundle\Service\CommissionPayoutService;
use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\Customer as Member;
use DbBundle\Repository\CommissionPeriodRepository;
use DbBundle\Repository\CustomerRepository as MemberRepository;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CommissionComputeCommand extends AbstractCommand
{
    public const COMMAND_NAME = 'commission:period:compute';
    private const OPTION_PERIOD = 'period';
    private const OPTION_MEMBER = 'member';
    private const OPTION_MEMBERS = 'members';
    private const OPTION_RECOMPUTE = 'recompute';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Compute the commissions for periods that was already completed')
            ->addOption(
                self::OPTION_PERIOD,
                'p',
                InputOption::VALUE_REQUIRED,
                'Period Id or use \"all\" if all available period', 'all'
            )
            ->addOption(self::OPTION_MEMBERS, 's', InputOption::VALUE_REQUIRED, 'Member Ids in json format', '[]')
            ->addOption(
                self::OPTION_MEMBER,
                'm',
                InputOption::VALUE_REQUIRED,
                'Member Id or use \"all\" for all referrer with commission within the period',
                'all'
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
        $period = $input->getOption('period');
        $memberId = $input->getOption('member');
        $executePayout = $input->hasOption('payout');
        $logger = new ConsoleLogger($output);

        if ($period === 'all') {
            do {
                $commissionPeriod = $this->getCommissionManager()->getCommissionPeriodIdThatWasNotYetComputed();
                if ($commissionPeriod instanceof CommissionPeriod) {
                    $process = new Process([
                        $this->getContainer()->getParameter('php_command'),
                        $this->getRootDirectory() . '/console',
                        $this->getName(),
                        $input->getArgument('username'),
                        '--period',
                        $commissionPeriod->getId(),
                        '--' . self::OPTION_RECOMPUTE,
                        $input->getOption(self::OPTION_RECOMPUTE),
                        '--env',
                        $this->getEnvironment(),
                    ]);
                    $process->setTimeout(null);
                    $output->writeln($process->getCommandLine());
                    $process->mustRun(function (string $type, string $buffer) use($output) {
                        $output->write($buffer);
                    });
                }
            } while ($commissionPeriod instanceof CommissionPeriod);
        } elseif ($memberId === 'all') {
            try {
                gc_enable();
                $commissionPeriod = $this->getCommissionPeriodRepository()->find($period);
                $commissionPeriod->setToComputing();
                $this->getCommissionPeriodRepository()->save($commissionPeriod, true);
                $output->writeln(sprintf(
                    'Execute Period [%s] %s -> %s',
                    $commissionPeriod->getId(),
                    $commissionPeriod->getDWLDateFrom()->format('Y-m-d'),
                    $commissionPeriod->getDWLDateTo()->format('Y-m-d')
                ));
                $this->getEntityManager()->getConnection()->getConfiguration()->setSQLLogger(null);
                $referrerIds = json_decode($input->getOption('members'), true);
                $members = $this
                    ->getCommissionPeriodRepository()
                    ->getReferrersIdForCommissionPeriod($commissionPeriod, $referrerIds);
                foreach ($members as $data) {
                    $process = new Process([
                        $this->getContainer()->getParameter('php_command'),
                        $this->getRootDirectory() . '/console',
                        $this->getName(),
                        $input->getArgument('username'),
                        '--period',
                        $commissionPeriod->getId(),
                        '--member',
                        $data[0]['id'],
                        '--' . self::OPTION_RECOMPUTE,
                        $input->getOption(self::OPTION_RECOMPUTE),
                        '--env',
                        $this->getEnvironment(),
                    ]);
                    $process->setTimeout(null);
                    $output->writeln($process->getCommandLine());
                    $process->mustRun(function (string $type, string $buffer) use($output) {
                        $output->write($buffer);
                    });
                    gc_collect_cycles();
                }
                $commissionPeriod->setToSuccessfullComputation();
                $this->getCommissionPeriodRepository()->save($commissionPeriod, true);
                gc_collect_cycles();
            } catch (Exception $ex) {
                $commissionPeriod->setToFailedComputation();
                $commissionPeriod->setDetails(['error' => $ex->getMessage()]);
                $this->getCommissionPeriodRepository()->save($commissionPeriod, true);

                throw $ex;
            }
        } else {
            $member = $this->getMemberRepository()->findById($memberId);
            $commissionPeriod = $this->getCommissionPeriodRepository()->find($period);
            $output->writeln(sprintf(
                'Process Compute for [%s] (%s) %s',
                $member->getId(),
                $member->getUser()->getUsername(),
                $member->getFullName()
            ));
            $forceRecompute = $input->getOption(self::OPTION_RECOMPUTE) == 1;
            $this->getCommissionPayoutService()->computeCommissionForMember($commissionPeriod, $member, $forceRecompute);
        }
    }

    private function getVerboseAsOption(OutputInterface $output): string
    {
        switch ($output->getVerbosity()) {
            case OutputInterface::VERBOSITY_VERBOSE:
                $verbose = '-v';
                break;
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $verbose = '--vv';
                break;
            case OutputInterface::VERBOSITY_DEBUG:
                $verbose = '--vvv';
                break;
            default:
                $verbose = '';
                break;
        }

        return $verbose;
    }

    private function getCommissionPeriodRepository(): CommissionPeriodRepository
    {
        return $this->getDoctrine()->getRepository(CommissionPeriod::class);
    }

    private function getRootDirectory(): string
    {
        return $this->getContainer()->get('kernel')->getRootDir();
    }

    private function getCommissionPayoutService(): CommissionPayoutService
    {
        return $this->getContainer()->get('commission.payout_service');
    }

    private function getMemberRepository(): MemberRepository
    {
        return $this->getDoctrine()->getRepository(Member::class);
    }

    private function getEnvironment(): string
    {
        return $this->getContainer()->get('kernel')->getEnvironment();
    }

    private function getCommissionManager(): CommissionManager
    {
        return $this->getContainer()->get('commission.manager');
    }
}
