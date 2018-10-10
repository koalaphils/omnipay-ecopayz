<?php

namespace DWLBundle\Command;

use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use DbBundle\Entity\DWL;
use DbBundle\Entity\Transaction;
use Symfony\Component\Console\Logger\ConsoleLogger;

class DwlGenerateFileCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('dwl:generate:file')
            ->setDescription('Generate Submited file from dwl')
            ->addArgument('dwl', InputArgument::REQUIRED, 'Argument description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);

        try {
            $this->createRequest();
            $dwl = $this->getDWLRepository()->find($input->getArgument('dwl'));
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
    }

    private function createRequest(): \Symfony\Component\HttpFoundation\Request
    {
        $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $this->getRequestStack()->push($request);

        return $request;
    }

    private function getRequestStack(): \Symfony\Component\HttpFoundation\RequestStack
    {
        return $this->getContainer()->get('request_stack');
    }

    private function getErrorMessage(\Exception $e)
    {
        return sprintf("%s\n%s:%s\n%s", $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    }

    private function getDWLFileGeneratorService(): \DWLBundle\Service\DWLFileGeneratorService
    {
        return $this->getContainer()->get('dwl.file_generator.service');
    }

    private function getDWLRepository(): \DbBundle\Repository\DWLRepository
    {
        return $this->getEntityManager()->getRepository(\DbBundle\Entity\DWL::class);
    }

    private function getEntityManager(string $name = 'default'): \Doctrine\ORM\EntityManager
    {
        return $this->getDoctrine()->getManager($name);
    }

    private function getDoctrine(): \Symfony\Bridge\Doctrine\RegistryInterface
    {
        return $this->getContainer()->get('doctrine');
    }
}
