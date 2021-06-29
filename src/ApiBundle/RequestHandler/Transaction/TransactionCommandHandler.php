<?php

declare(strict_types = 1);

namespace ApiBundle\RequestHandler\Transaction;

use DbBundle\Entity\Customer;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\TransactionRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;

class TransactionCommandHandler
{
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(TransactionRepository $transactionRepository, EntityManager $entityManager)
    {
        $this->transactionRepository = $transactionRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @param Customer $member
     * @return Transaction
     *
     * @throws NoResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function handleAcknowledgeBitcoin(Customer $member): Transaction
    {
        $transaction = $this->transactionRepository->findUserUnacknowledgedDepositBitcoinTransaction((int) $member->getId());
        if ($transaction === null) {
            throw new NoResultException();
        }

        $transaction->setBitcoinAcknowledgedByUser(true);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush($transaction);

        return $transaction;
    }
}