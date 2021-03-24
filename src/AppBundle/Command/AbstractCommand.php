<?php

namespace AppBundle\Command;

use DbBundle\Entity\User;
use DbBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

abstract class AbstractCommand extends ContainerAwareCommand
{
    protected function getNow()
    {
        $date = new \DateTime();

        return $date->format('Y-m-d H:i:s');
    }

    protected function write($string, $params, OutputInterface $output, $newLine = false, $withDate = true)
    {
        if ($withDate) {
            $string = "[%s] $string";
            array_unshift($params, $this->getNow());
        }
        $output->write(vsprintf($string, $params), $newLine);
    }

    protected function writeln($string, $params, OutputInterface $output, $withDate = true)
    {
        $this->write($string, $params, $output, true, $withDate);
    }

    protected function writeError(\Exception $e, OutputInterface $output, $trace = [])
    {
        $message = "ERR >> [%s] %s ( %s line %s )";
        $params = [$e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()];
        $this->writeln($message, $params, $output);
        $trace = [];
        if ($e->getPrevious() !== null) {
            $this->writeError($e->getPrevious(), $output, $trace);
        }
        $trace[] = $e->getTraceAsString();
        $this->writeln("\n%s\n", [implode("\n", $trace)], $output, false);
    }

    protected function loginUser(string $username): User
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

    protected function getUserRepository(): UserRepository
    {
        return $this->getEntityManager()->getRepository(User::class);
    }

    protected function getEntityManager(string $name = 'default'): EntityManager
    {
        return $this->getDoctrine()->getManager($name);
    }

    protected function getDoctrine(): RegistryInterface
    {
        return $this->getContainer()->get('doctrine');
    }

    protected function getRequestStack(): RequestStack
    {
        return $this->getContainer()->get('request_stack');
    }
}
