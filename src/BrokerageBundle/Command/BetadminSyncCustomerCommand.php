<?php

namespace BrokerageBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use DbBundle\Entity\Transaction;

class BetadminSyncCustomerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('betadmin:sync-customer')
            ->setDescription('sync customer to bet admin')
            ->addArgument('transaction', InputArgument::REQUIRED)
            ->addArgument('userId', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('transaction')) {
            $this->createRequest();
            $transaction = $this->getTransactionRepository()->findOneById($input->getArgument('transaction'));

            if ($transaction instanceof Transaction) {
                $this->setUserId($input->getArgument('userId'));
                $this->getBrokerageManager()->syncFirstTransaction($transaction);
            } else {
                throw new \Doctrine\ORM\NoResultException;
            }
        }
    }

    protected function setUserId($userId)
    {
        $user = $this->getUserManager()->getRepository()->findOneById($userId);
        if ($user === null) {
            throw new UsernameNotFoundException('User not found ' . $userId);
        }
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->getContainer()->get('security.token_storage')->setToken($token);

        $event = new InteractiveLoginEvent(new \Symfony\Component\HttpFoundation\Request(), $token);
        $this->getContainer()->get('event_dispatcher')->dispatch('security.interactive_login', $event);
    }

    protected function getTransactionRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('DbBundle:Transaction');
    }

    /**
     * Get user manager.
     *
     * @return \UserBundle\Manager\UserManager
     */
    protected function getUserManager()
    {
        return $this->getContainer()->get('user.manager');
    }

    private function getBrokerageManager(): \BrokerageBundle\Manager\BrokerageManager
    {
        return $this->getContainer()->get('brokerage.brokerage_manager');
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
}
