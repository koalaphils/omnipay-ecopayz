<?php

namespace TransactionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class TransactionDeclineCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('transaction:decline')
            ->setDescription('Decline Transaction')
            ->setHelp('This will help the CS to decline transaction automatically due to no deposit received in payment gateway')
            ->addArgument('username', InputArgument::REQUIRED, 'user username')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new ConsoleLogger($output);

        try {
            $this->createRequest();
            $user = $this->loginUser($input->getArgument('username'));
            $roles = $user->getRoles();

            if (!in_array('role.scheduler', $roles)) {
                throw new \Exception('Access Denied.');
            }
            $logger->info(sprintf('Login User: %s [%s]', $user->getUsername(), $user->getId()));

            $service = $this->getTransactionDeclineService();

            $isReadyToDecline = $service->getAutoDeclineStatus();
            if (!$isReadyToDecline) {
                throw new \Exception('Task scheduler not active.');
            }

            $service->setLogger($logger);
            $returnedResult = $service->processDeclining();
            if (!empty($returnedResult['result'])) {
                $logger->info('Declined Ids: ' . json_encode($returnedResult['result']));
            } else {
                $logger->info('Nothing to decline');
            }
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

    private function loginUser(string $username): \DbBundle\Entity\User
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

    private function getTransactionDeclineService(): \TransactionBundle\Service\TransactionDeclineService
    {
        return $this->getContainer()->get('transaction.decline.service');
    }

    private function getTransactionRepository(): \TransactionBundle\Repository\TransactionRepository
    {
        return $this->getEntityManager()->getRepository(\DbBundle\Entity\Transaction::class);
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
