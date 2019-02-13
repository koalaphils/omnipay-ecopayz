<?php

namespace DWLBundle\Command;

use AppBundle\Command\AbstractCommand;
use DbBundle\Entity\DWL;
use DbBundle\Repository\DWLRepository;
use DWLBundle\Service\DWLFileGeneratorService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DwlGenerateFileCommand extends AbstractCommand
{
    public const COMMAND_NAME = 'dwl:generate:file';
    private const ARGUMENT_DWL = 'dwl';
    private const ARGUMENT_USERNAME = 'username';
    private const OPTION_DATE_FROM = 'dateFrom';
    private const OPTION_DATE_TO = 'dateTo';
    private const OPTION_PRODUCT = 'product';
    private const DEFAULT_DATE_VALUE = '0000-00-00';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Generate Submited file from dwl')
            ->addArgument(self::ARGUMENT_DWL, InputArgument::REQUIRED, 'Argument description')
            ->addArgument(self::ARGUMENT_USERNAME, InputArgument::REQUIRED, 'Username')
            ->addOption(self::OPTION_DATE_FROM, null, InputOption::VALUE_OPTIONAL, 'Date From (Y-m-d)', self::DEFAULT_DATE_VALUE)
            ->addOption(self::OPTION_DATE_TO, null, InputOption::VALUE_OPTIONAL, 'Date From (Y-m-d)', self::DEFAULT_DATE_VALUE)
            ->addOption(self::OPTION_PRODUCT, null, InputOption::VALUE_OPTIONAL, 'Product Id', '0')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dwlId = $input->getArgument(self::ARGUMENT_DWL);
        if ($dwlId != '0') {
            $this->executeDwl($input, $output);
        } else {
            $this->executeDateRange($input, $output);
        }
    }

    protected function executeDateRange(InputInterface $input, OutputInterface $output): void
    {
        $dateFrom = \DateTimeImmutable::createFromFormat('Y-m-d', $input->getOption(self::OPTION_DATE_FROM));
        $dateTo = \DateTimeImmutable::createFromFormat('Y-m-d', $input->getOption(self::OPTION_DATE_TO));
        $productId = (int) $input->getOption(self::OPTION_PRODUCT);
        $verbose = $this->getVerboseAsOption($output);

        $dwlIds = $this->getDWLRepository()->getDWLIdsFromRangeAndProduct($productId, $dateFrom, $dateTo);
        foreach ($dwlIds as $dwlScalar) {
            $command = [
                $this->getContainer()->getParameter('php_command'),
                $this->getRootDirectory() . '/console',
                $this->getName(),
                $dwlScalar['id'],
                $input->getArgument(self::ARGUMENT_USERNAME),
                '--env',
                $this->getEnvironment(),
            ];

            if ($verbose !== '') {
                $command[] = $verbose;
            }

            $process = new Process($command);
            $process->setTimeout(null);

            $output->writeln($process->getCommandLine());
            $process->mustRun(function (string $type, string $buffer) use($output) {
                $output->write($buffer);
            });
        }

        $runTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 4);
        $output->writeln(sprintf('Runtime: %s ms', $runTime));
    }

    protected function executeDwl(InputInterface $input, OutputInterface $output): void
    {
        $logger = new ConsoleLogger($output);

        try {
            $this->loginUser($input->getArgument(self::ARGUMENT_USERNAME));
            $dwl = $this->getDWLRepository()->find($input->getArgument(self::ARGUMENT_DWL));
            if ($dwl === null) {
                throw new \Exception(sprintf('DWL with id %s does not exists', $input->getOption('id')));
            }

            $service = $this->getDWLFileGeneratorService();
            $service->setLogger($logger);
            $service->processDWl($dwl);
        } catch (\Exception $e) {
            $logger->error($this->getErrorMessage($e), [
                'class' => get_class($e),
            ]);
        }

        $runTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 4);
        $logger->info(sprintf('Runtime: %s ms', $runTime));
        $logger->info(sprintf('Memory Peak: %s', memory_get_peak_usage(true)));
    }

    private function getErrorMessage(\Exception $e)
    {
        return sprintf("%s\n%s:%s\n%s", $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    }

    private function getDWLFileGeneratorService(): DWLFileGeneratorService
    {
        return $this->getContainer()->get('dwl.file_generator.service');
    }

    private function getDWLRepository(): DWLRepository
    {
        return $this->getEntityManager()->getRepository(DWL::class);
    }

    private function getRootDirectory(): string
    {
        return $this->getContainer()->get('kernel')->getRootDir();
    }

    private function getEnvironment(): string
    {
        return $this->getContainer()->get('kernel')->getEnvironment();
    }

    private function getVerboseAsOption(OutputInterface $output): string
    {
        switch ($output->getVerbosity()) {
            case OutputInterface::VERBOSITY_VERBOSE:
                $verbos = '-v';
                break;
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $verbos = '-vv';
                break;
            case OutputInterface::VERBOSITY_DEBUG:
                $verbos = '-vvv';
                break;
            default:
                $verbos = '';
                break;
        }

        return $verbos;
    }
}
