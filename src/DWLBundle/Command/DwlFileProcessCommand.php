<?php

namespace DWLBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Input\InputArgument;

class DwlFileProcessCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('dwl:file:process')
            ->setDescription('Process the dwl')
            ->addArgument('id', InputArgument::REQUIRED, "Daily Win Loss Id")
            ->addArgument('username', InputArgument::REQUIRED, "Username of uploader")
        ;
    }

    protected function loginUser(string $username): \DbBundle\Entity\User
    {
        $user = $this->getUserRepository()->loadUserByUsername($username);
        if ($user === null) {
            throw new UsernameNotFoundException('User not found');
        }
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->getContainer()->get('security.token_storage')->setToken($token);

        $event = new InteractiveLoginEvent(new \Symfony\Component\HttpFoundation\Request(), $token);
        $this->getContainer()->get('event_dispatcher')->dispatch('security.interactive_login', $event);

        return $user;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);
        try {
            $this->createRequest();
            $user = $this->loginUser($input->getArgument('username'));
            $logger->info(sprintf('Login User: %s [%s]', $user->getUsername(), $user->getId()));

            $dwl = $this->getDWLRepository()->find($input->getArgument('id'));
            if ($dwl === null) {
                throw new \Exception(sprintf('DWL with id %s does not exists', $input->getOption('id')));
            }
            $service = $this->getDWLFileProcessService();
            $service->setLogger($logger);
            $service->processDWL($dwl);
        } catch (\Exception $e) {
            $logger->error($e->getMessage(), ['class' => get_class($e)]);
        }

        $runTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 4);
        $logger->info(sprintf('Runtime: %s ms', $runTime));
    }

    private function getErrorMessage(\Exception $e)
    {
        return sprintf("%s\n%s:%s\n%s", $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
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

    private function getDWLFileProcessService(): \DWLBundle\Service\DWLFileProcessService
    {
        return $this->getContainer()->get('dwl.file_process.service');
    }

    private function getDWLRepository(): \DbBundle\Repository\DWLRepository
    {
        return $this->getEntityManager()->getRepository(\DbBundle\Entity\DWL::class);
    }

    private function getUserRepository(): \DbBundle\Repository\UserRepository
    {
        return $this->getEntityManager()->getRepository(\DbBundle\Entity\User::class);
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
