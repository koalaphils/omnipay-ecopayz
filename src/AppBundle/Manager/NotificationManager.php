<?php

namespace AppBundle\Manager;

class NotificationManager extends AbstractManager
{
    public function getList(): array
    {
        $limit = 10;
        $latestTransactions = $this->getTransactionRepository()->getLatestCreatedTransactions($limit);
        $latestTransactions = array_map(function ($transaction) {
            $data = $transaction;
            $data['notificationType'] = 'transaction';

            return $data;
        }, $latestTransactions);
        $latestCustomers = $this->getCustomerRepository()->getLatestCreatedCustomers($limit);
        $latestCustomers = array_map(function ($customer) {
            $data = $customer;
            $data['notificationType'] = 'customer';

            return $data;
        }, $latestCustomers);
        $lastRead = $this->getLastReadNotification();
        $mergedNotifications = array_merge($latestCustomers, $latestTransactions);
        $dateCreated = [];

        foreach ($mergedNotifications as $record) {
            $dateCreated[] = $record['createdAt'];
        }

        array_multisort(
            $dateCreated, SORT_DESC,
            $mergedNotifications
        );

        $result = [
            'list' => $mergedNotifications,
            'lastRead' => $lastRead
        ];

        return $result;
    }

    public function saveLastRead()
    {
        try {
            /* @var $user \DbBundle\Entity\User */
            $user = $this->getUser();
            $user->setPreference('lastReadNotification', new \DateTimeImmutable);
            $this->save($user);
            $response = ['error' => false];
        } catch (\PDOException $e) {
            $this->rollback();
            $response = ['error' => true];
            throw $e;
        }

        return $response;
    }

    /**
     * @return array
     */
    public function getLastReadNotification(): array
    {
        /* @var $user \DbBundle\Entity\User */
        $user = $this->getUser();
        return $user->getPreference('lastReadNotification') ? $user->getPreference('lastReadNotification') : [];
    }

    /**
     * @return \DbBundle\Repository\TransactionRepository
     */
    public function getTransactionRepository(): \DbBundle\Repository\TransactionRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Transaction');
    }

    /**
     * @return \DbBundle\Repository\CustomerRepository
     */
    public function getCustomerRepository(): \DbBundle\Repository\CustomerRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Customer');
    }

    public function getRepository()
    {
    }
}