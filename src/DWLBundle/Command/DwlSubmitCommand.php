<?php

namespace DWLBundle\Command;

use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\DWL;
use DbBundle\Entity\User;
use DbBundle\Repository\CommissionPeriodRepository;
use DbBundle\Repository\DWLRepository;
use DbBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use DWLBundle\Service\DWLFileGeneratorService;
use DWLBundle\Service\DWLSubmitService;
use JMS\JobQueueBundle\Entity\Job;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class DwlSubmitCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('dwl:submit')
            ->setDescription('Submit DWL')
            ->addArgument('id', InputArgument::REQUIRED, 'dwl id')
            ->addArgument('username', InputArgument::REQUIRED, 'user username')
            ->addOption('submit', null, InputOption::VALUE_OPTIONAL, 'force', 'restrict')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(0);
        $logger = new ConsoleLogger($output);

        try {
            $this->createRequest();
            $user = $this->loginUser($input->getArgument('username'));
            $logger->info(sprintf('Login User: %s [%s]', $user->getUsername(), $user->getId()));

            $dwl = $this->getDWLRepository()->find($input->getArgument('id'));
            if ($dwl === null) {
                throw new \Exception(sprintf('DWL with id %s does not exists', $input->getOption('id')));
            }

            $service = $this->getDWLSubmitService();
            $service->setLogger($logger);
            $forceSubmition = false;
            if ($input->getOption('submit') === 'force') {
                $forceSubmition = true;
            }
            $service->processDWl($dwl, $forceSubmition);
            $this->regenerateFile($dwl, $logger);
            
            $period = $this->getCommissionPeriodRepository()->getCommissionForDWL($dwl);
            if ($period instanceof CommissionPeriod) {
                $computeJob = new Job('revenueshare:period:compute',
                    [
                        $input->getArgument('username'),
                        '--period',
                        $period->getId(),
                        '--env',
                        $this->getContainer()->get('kernel')->getEnvironment(),
                    ],
                    true,
                    'payout'
                );
                $payoutJob = new Job('revenueshare:period:pay',
                    [
                        $input->getArgument('username'),
                        '--period',
                        $period->getId(),
                        '--env',
                        $this->getContainer()->get('kernel')->getEnvironment(),
                    ],
                    true,
                    'payout'
                );
                $payoutJob->addDependency($computeJob);
                $this->getEntityManager()->persist($computeJob);
                $this->getEntityManager()->persist($payoutJob);
                $this->getEntityManager()->flush($computeJob);
                $this->getEntityManager()->flush($payoutJob);
            }
        } catch (\Exception $e) {
            $logger->error($this->getErrorMessage($e), [
                'class' => get_class($e),
            ]);
        }

        $runTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 4);
        $logger->info(sprintf('Runtime: %s ms', $runTime));
    }

    private function createRequest(): Request
    {
        $request = Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $this->getRequestStack()->push($request);

        return $request;
    }

    private function getRequestStack(): RequestStack
    {
        return $this->getContainer()->get('request_stack');
    }

    private function getErrorMessage(\Exception $e)
    {
        return sprintf("%s\n%s:%s\n%s", $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    }

    private function loginUser(string $username): User
    {
        $user = $this->getUserRepository()->loadUserByUsername($username);
        if ($user === null) {
            throw new UsernameNotFoundException('User not found');
        }
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->getContainer()->get('security.token_storage')->setToken($token);

        $event = new InteractiveLoginEvent(new Request(), $token);
        $this->getContainer()->get('event_dispatcher')->dispatch('security.interactive_login', $event);

        return $user;
    }

    private function regenerateFile(DWL $dwl, LoggerInterface $logger): void
    {
        $service = $this->getDWLFileGeneratorService();
        $service->setLogger($logger);
        $service->processDWl($dwl);
    }

    private function getDWLFileGeneratorService(): DWLFileGeneratorService
    {
        return $this->getContainer()->get('dwl.file_generator.service');
    }

    private function getDWLSubmitService(): DWLSubmitService
    {
        return $this->getContainer()->get('dwl.submit.service');
    }

    private function getDWLRepository(): DWLRepository
    {
        return $this->getEntityManager()->getRepository(DWL::class);
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
    
    private function getCommissionPeriodRepository(): CommissionPeriodRepository
    {
        return $this->getDoctrine()->getRepository(CommissionPeriod::class);
    }
    
    private function getRootDirectory(): string
    {
        return $this->getContainer()->get('kernel')->getRootDir();
    }
}
