<?php

namespace MemberRequestBundle\Service;

use Psr\Log\LoggerInterface;
use DbBundle\Repository\TransactionRepository;
use DbBundle\Repository\UserRepository;
use DbBundle\Entity\PaymentOption;
use DbBundle\Entity\User;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\MemberRequest;
use DbBundle\Repository\MemberRequestRepository;
use AppBundle\Manager\SettingManager;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\EntityManager;

abstract class AbstractMemberRequestService
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    protected $logger = null;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function log(string $message)
    {
        if (!$this->hasLogger()) {
            return;
        }

        $this->logger->info($message, []);
    }

    protected function hasLogger(): bool
    {
        return $this->logger instanceof LoggerInterface;
    }

    protected function getMemberRequestRepository(): MemberRequestRepository
    {
        return $this->getEntityManager()->getRepository(MemberRequest::class);
    }

    protected function getTransactionRepository(): TransactionRepository
    {
        return $this->getEntityManager()->getRepository(Transaction::class);
    }

    protected function getUserRepository(): UserRepository
    {
        return $this->getEntityManager()->getRepository(User::class);
    }

    protected function getDoctrine(): RegistryInterface
    {
        return $this->getService('doctrine');
    }

    protected function getEntityManager(string $name = 'default'): EntityManager
    {
        return $this->getDoctrine()->getManager($name);
    }

    protected function getService(string $serviceName)
    {
        return $this->container->get($serviceName);
    }
}
