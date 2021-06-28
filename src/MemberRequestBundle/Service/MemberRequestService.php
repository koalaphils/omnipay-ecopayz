<?php

namespace MemberRequestBundle\Service;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use DbBundle\Entity\User;

class MemberRequestService extends AbstractMemberRequestService
{
    public function createHttpRequest(): Request
    {
        $request = Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $this->getRequestStack()->push($request);

        return $request;
    }

    public function loginUserByUsername(string $username = ''): User
    {
        $user = $this->getUserRepository()->loadUserByUsername($username);
        if ($user === null) {
            throw new UsernameNotFoundException('User not found');
        }
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->getSecurityToken()->setToken($token);

        $event = new InteractiveLoginEvent(new Request(), $token);
        $this->getEventDispatcher()->dispatch('security.interactive_login', $event);

        return $user;
    }

    public function setLoggerForUser(User $user, ConsoleLogger $logger): void
    {
        $roles = $user->getRoles();

        if (!in_array('role.member.request.delete', $roles)) {
            throw new \Exception('Access Denied.');
        }
        
        $logger->info(sprintf('Login User: %s [%s]', $user->getUsername(), $user->getId()));
    }

    public function setMemberRequestLogger($logger): void
    {
        $this->setLogger($logger);
    }

    public function processMigrationForProductPassword(): void
    {
        $resetProductTransactions = $this->getTransactionRepository()->findResetProductTransactions();
        if (!empty($resetProductTransactions)) {
            foreach ($resetProductTransactions as $resetProductTransaction) {
                $candidateTransactionRecordForDeletion = $this->getMemberRequestRepository()->migrateProductPassword($resetProductTransaction);
                if (!empty($candidateTransactionRecordForDeletion)) {
                    $this->log('To be deleted ids: ' . json_encode($candidateTransactionRecordForDeletion));
                    $this->getTransactionRepository()->deleteResetProductInTransactions($candidateTransactionRecordForDeletion);
                }
            }
        } else {
            $this->log('No transaction found');
        }
    }

    private function getRequestStack(): RequestStack
    {
        return $this->container->get('request_stack');
    }

    private function getSecurityToken(): TokenStorage
    {
        return $this->container->get('security.token_storage');
    }

    protected function getEventDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }
}