<?php

namespace BrokerageBundle\Command;

use BrokerageBundle\Component\BrokerageInterface;
use BrokerageBundle\Exceptions\NoSubTransactionException;
use BrokerageBundle\Service\RecomputeDwlService;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\DWL;
use DbBundle\Entity\Product;
use DbBundle\Entity\User;
use DbBundle\Repository\CustomerProductRepository as MemberProductRepository;
use DbBundle\Repository\DWLRepository;
use DbBundle\Repository\ProductRepository;
use DbBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use DWLBundle\Command\DwlGenerateFileCommand;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class BetadminUpdateDwlCommand extends ContainerAwareCommand
{
    public const COMMAND_NAME = 'brokerage:update:dwl';
    private const ARGUMENT_USERNAME = 'username';
    private const OPTION_UPDATE_CUSTOMER_BALANCE = 'updatebalance';
    private const OPTION_DWL_DATE = 'dwlDate';
    private const OPTION_DWL_DATE_FROM = 'dwlDateFrom';
    private const OPTION_DWL_DATE_TO = 'dwlDateTo';
    private const DEFAULT_DATE_VALUE = '0000-00-00';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->addArgument(self::ARGUMENT_USERNAME, InputArgument::REQUIRED, "Username")
            ->addOption(self::OPTION_DWL_DATE, null, InputOption::VALUE_OPTIONAL, 'DWL date (Y-m-d)', self::DEFAULT_DATE_VALUE)
            ->addOption(self::OPTION_DWL_DATE_FROM, null, InputOption::VALUE_OPTIONAL, 'DWL date (Y-m-d)', self::DEFAULT_DATE_VALUE)
            ->addOption(self::OPTION_DWL_DATE_TO, null, InputOption::VALUE_OPTIONAL, 'DWL date (Y-m-d)', self::DEFAULT_DATE_VALUE)
            ->addOption(self::OPTION_UPDATE_CUSTOMER_BALANCE, null, InputOption::VALUE_OPTIONAL, 'Update customer balance', '1')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(0);
        $input->getOptions('');
        if ($input->getOption(self::OPTION_DWL_DATE) === self::DEFAULT_DATE_VALUE) {
            $this->executeRange($input, $output);
        } else {
            $this->executeDate($input, $output);
        }

        $runTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 4);
        $output->writeln(sprintf('Runtime: %s ms', $runTime));
    }

    protected function executeRange(InputInterface $input, OutputInterface $output): void
    {
        $dwlDateFrom = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $input->getOption(self::OPTION_DWL_DATE_FROM) . ' 00:00:00');
        $dwlDateTo = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $input->getOption(self::OPTION_DWL_DATE_TO) . ' 00:00:00');
        $dwlDate = $dwlDateFrom;

        do {
            $process = new Process([
                $this->getContainer()->getParameter('php_command'),
                $this->getRootDirectory() . '/console',
                $this->getName(),
                $input->getArgument('username'),
                '--' . self::OPTION_DWL_DATE,
                $dwlDate->format('Y-m-d'),
                '--' . self::OPTION_UPDATE_CUSTOMER_BALANCE,
                $input->getOption(self::OPTION_UPDATE_CUSTOMER_BALANCE),
                '--env',
                $this->getEnvironment(),
                $this->getVerboseAsOption($output),
            ]);
            $process->setTimeout(null);

            $output->writeln($process->getCommandLine());
            $process->mustRun(function (string $type, string $buffer) use($output) {
                $output->write($buffer);
            });

            $dwlDate = $dwlDate->modify('+1 day');
        } while ($dwlDate <= $dwlDateTo);
    }

    protected function executeDate(InputInterface $input, OutputInterface $output)
    {
        $user = $this->loginUser($input->getArgument(self::ARGUMENT_USERNAME));
        $dwlDate = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $input->getOption(self::OPTION_DWL_DATE) . ' 00:00:00');
        $isUpdateBalance = $input->getOption(self::OPTION_UPDATE_CUSTOMER_BALANCE);

        $limit = 20;
        $offset = 0;
        $dwls = [];
        $dwlSubtransactionIds = [];
        do {
            $output->writeln(PHP_EOL . '===== Date: ' . $input->getOption(self::OPTION_DWL_DATE) . '  Offset: '. $offset . ' == Limit: ' . $limit . ' =====');
            $loop = true;
            $responseData = $this->getBrokerage()->getMembersComponent()->getMemberToFix($dwlDate, $offset, $limit);
            if ($dwlDate->format('Y-m-d') === $responseData['date']) {
                $members = $responseData['members'] ?? [];
                if (!empty($members)) {
                    foreach ($members as $memberDetails) {
                        $output->writeln('Processing Member Sync Id: ' . $memberDetails['sync_id']);
                        try {
                            $output->writeln('Data to process: ' . json_encode($memberDetails));
                            $memberProduct = $this
                                ->getMemberProductRepository()->getSyncedMemberProduct($memberDetails['sync_id']);
                            if (!is_null($memberProduct)) {
                                $output->writeln('Member Product: ' . $memberProduct->getUserName() . ' (' . $memberProduct->getId() . ')');
                                if (array_has($dwls, $memberProduct->getCurrency()->getCode())) {
                                    $dwl = $dwls[$memberProduct->getCurrency()->getCode()];
                                } else {
                                    $dwl = $this->getDwlRepository()->findDWLByDateProductAndCurrency(
                                        $memberProduct->getProduct()->getId(),
                                        $memberProduct->getCurrency()->getId(),
                                        $dwlDate
                                    );
                                    $dwls[$memberProduct->getCurrency()->getCode()] = $dwl;
                                    $dwlSubtransactionIds[$dwl->getId()] = [];
                                }

                                $dwlSubtransactionIds[$dwl->getId()][] = $this->getRecomputeDwlService()->updateDWLForMemberProduct(
                                    $memberProduct,
                                    $dwl,
                                    $memberDetails['win_loss'],
                                    $memberDetails['stake'],
                                    $memberDetails['turnover'],
                                    $isUpdateBalance == '1' ? $memberDetails['current_balance'] : null,
                                    false
                                )->getId();
                                $output->writeln('Done Member Sync Id: ' . $memberDetails['sync_id']);
                            } else {
                                $output->writeln('Will Skip Member Sync Id: ' . $memberDetails['sync_id'] . ' due to no available member product with this sync id.');
                            }
                        } catch (NoSubTransactionException $e) {
                            $output->writeln('Will Skip Member Sync Id: ' . $memberDetails['sync_id'] . ' due to nothing to be fix.');
                        }
                    }
                } else {
                    $loop = false;
                }
            } else {
                $loop = false;
                $output->writeln(sprintf(
                    'Unable to process due to wrong date, %s is expected but %s is given',
                    $dwlDate->format('Y-m-d'),
                    $responseData['date']
                ));
            }
            $offset += $limit;
        } while ($loop);

        foreach ($dwls as $dwl) {
            $allSubTransactonIds = $this->getSubTransactionRepository()->getDwlSubTransactioIds([$dwl->getId()]);
            foreach ($allSubTransactonIds as $subTransacitonId) {
                if (!in_array($subTransacitonId['id'], $dwlSubtransactionIds[$dwl->getId()])) {
                    $this->getRecomputeDwlService()->changeToZeroSubtransaction($dwl, $subTransacitonId['id'], false);
                }
            }
        }
    }

    private function regenerateDwlFile(DWL $dwl, User $user): void
    {
        $rootDir = $this->getContainer()->get('kernel')->getRootDir();
        $command = [
            $this->getContainer()->getParameter('php_command'),
            $rootDir . "/console",
            DwlGenerateFileCommand::COMMAND_NAME,
            $dwl->getId(),
            $user->getUsername(),
            '--env=' . $this->getContainer()->get('kernel')->getEnvironment(),
        ];

        $process = new Process(implode(' ', $command));
        $process->run();
    }

    private function loginUser(string $username): User
    {
        $user = $this->getUserRepository()->loadUserByUsername($username);
        if ($user === null) {
            throw new UsernameNotFoundException('User not found');
        }
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->getContainer()->get('security.token_storage')->setToken($token);
        $this->getRequestStack()->push(Request::create(''));

        return $user;
    }

    private function getBrokerage(): BrokerageInterface
    {
        return $this->getContainer()->get('brokerage.brokerage_service');
    }

    private function getRecomputeDwlService(): RecomputeDwlService
    {
        return $this->getContainer()->get('brokerage.recompute_dwl_service');
    }

    private function getDwlRepository(): DWLRepository
    {
        return $this->getEntityManager()->getRepository(DWL::class);
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->getEntityManager()->getRepository(Product::class);
    }

    private function getUserRepository(): UserRepository
    {
        return $this->getEntityManager()->getRepository(User::class);
    }

    private function getEntityManager(string $name = 'default'): EntityManager
    {
        return $this->getDoctrine()->getManager($name);
    }

    private function getDoctrine(): RegistryInterface
    {
        return $this->getContainer()->get('doctrine');
    }

    private function getRequestStack(): RequestStack
    {
        return $this->getContainer()->get('request_stack');
    }

    private function getMemberProductRepository(): MemberProductRepository
    {
        return $this->getEntityManager()->getRepository(MemberProduct::class);
    }

    private function getEnvironment(): string
    {
        return $this->getContainer()->get('kernel')->getEnvironment();
    }

    private function getRootDirectory(): string
    {
        return $this->getContainer()->get('kernel')->getRootDir();
    }

    private function getVerboseAsOption(OutputInterface $output): string
    {
        switch ($output->getVerbosity()) {
            case OutputInterface::VERBOSITY_VERBOSE:
                $verbos = '-v';
                break;
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $verbos = '--vv';
                break;
            case OutputInterface::VERBOSITY_DEBUG:
                $verbos = '--vvv';
                break;
            default:
                $verbos = '';
                break;
        }

        return $verbos;
    }

    private function getSubTransactionRepository(): \DbBundle\Repository\SubTransactionRepository
    {
        return $this->getEntityManager()->getRepository(\DbBundle\Entity\SubTransaction::class);
    }
}
