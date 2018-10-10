<?php

namespace CommissionBundle\Command;

use CommissionBundle\Manager\CommissionManager;
use CommissionBundle\Service\CommissionPayoutService;
use DateTime;
use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\Customer as Member;
use DbBundle\Repository\CommissionPeriodRepository;
use DbBundle\Repository\CustomerRepository as MemberRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class CommissionPayoutCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('commission:period:pay')
            ->setDescription('Payout the commissions for periods')
            ->addOption(
                'period',
                'p',
                InputOption::VALUE_REQUIRED,
                'Period Id or use \"all\" if all available period', 'all'
            )
            ->addOption('members', 's', InputOption::VALUE_REQUIRED, 'Member Ids in json format', '[]')
            ->addOption(
                'member',
                'm',
                InputOption::VALUE_REQUIRED,
                'Member Id or use \"all\" for all referrer with commission within the period',
                'all'
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
        $logger = new ConsoleLogger($output);
        
        if ($period === 'all') {
            do {
                $commissionPeriod = $this->getCommissionManager()->getCommissionPeriodIdThatWasNotPaid();
                if ($commissionPeriod instanceof CommissionPeriod) {
                    $process = new Process([
                        $this->getContainer()->getParameter('php_command'),
                        $this->getRootDirectory() . '/console',
                        $this->getName(),
                        $input->getArgument('username'),
                        '--period',
                        $commissionPeriod->getId(),
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
            gc_enable();
            $commissionPeriod = $this->getCommissionPeriodRepository()->find($period);
            $now = new DateTime('now');
            if ($commissionPeriod->getPayoutAt() <= $now) {
                $commissionPeriod->setToExecutingPayout();
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
                $commissionPeriod->setToSuccessfullPayout();
                $this->getCommissionPeriodRepository()->save($commissionPeriod, true);
            }
            gc_collect_cycles();
        } else {
            $member = $this->getMemberRepository()->findById($memberId);
            $commissionPeriod = $this->getCommissionPeriodRepository()->find($period);
            $output->writeln(sprintf(
                'Process Payout for [%s] (%s) %s',
                $member->getId(),
                $member->getUser()->getUsername(),
                $member->getFullName()
            ));
            $this->getCommissionPayoutService()->payoutCommissionForMember($commissionPeriod, $member);
        }
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
