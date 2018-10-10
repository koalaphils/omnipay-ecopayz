<?php

namespace CommissionBundle\Command;

use DbBundle\Entity\User;
use DbBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

abstract class AbstractCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Argument description');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->createRequest();
        $this->loginUser($input->getArgument('username'));
    }
    
    protected function createRequest(): Request
    {
        $request = Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $this->getRequestStack()->push($request);

        return $request;
    }
    
    protected function loginUser(string $username): User
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
    
    protected function getRequestStack(): RequestStack
    {
        return $this->getContainer()->get('request_stack');
    }

    protected function getUserRepository(): UserRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(User::class);
    }
    
    protected function getEntityManager(): EntityManager
    {
        return $this->getDoctrine()->getManager();
    }
    
    protected function getDoctrine(): Registry
    {
        return $this->getContainer()->get('doctrine');
    }
}
