<?php

namespace AppBundle\Manager;

use DbBundle\Repository\CustomerProductRepository as MemberProductRepository;
use DbBundle\Repository\CustomerRepository as MemberRepository;
use DbBundle\Repository\TransactionRepository;
use DbBundle\Repository\MemberRequestRepository;
use Doctrine\ORM\Query;

class NotificationManager extends AbstractManager
{
    private $memberProductRepository;

    public function __construct(MemberProductRepository $memberProductRepository)
    {
        $this->memberProductRepository = $memberProductRepository;
    }

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

        $latestRequestedProducts = $this->getMemberProductRepository()->getRequestList($limit, 0, Query::HYDRATE_ARRAY);
        $latestRequestedProducts = array_map(function ($product) {
            $data = $product;
            $data['createdAt'] = $product['requestedAt'];
            $data['notificationType'] = 'requestProduct';

            return $data;
        }, $latestRequestedProducts);

        $filter = ['limit'=>$limit];
        $order = [['column' => 'mrs.updatedAt', 'dir' => 'desc']];
        
        $memberRequestNotifications = $this->getMemberRequestRepository()->getRequestList($filter, $order, Query::HYDRATE_ARRAY);

        $lastRead = $this->getLastReadNotification();
        $mergedNotifications = array_merge($latestCustomers, $latestTransactions, $latestRequestedProducts, $memberRequestNotifications);
        $dateCreated = [];

        foreach ($mergedNotifications as $record) {
            $dateCreated[] = array_get($record, 'updatedAt', $record['createdAt']);
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

    public function getLastReadNotification(): array
    {
        $user = $this->getUser();
        return $user->getPreference('lastReadNotification') ? $user->getPreference('lastReadNotification') : [];
    }

    public function getTransactionRepository(): TransactionRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Transaction');
    }

    public function getCustomerRepository(): MemberRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Customer');
    }

    public function getRepository()
    {
    }

    private function getMemberProductRepository(): MemberProductRepository
    {
        return $this->memberProductRepository;
    }

    private function getMemberRequestRepository(): MemberRequestRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:MemberRequest');
    }
}