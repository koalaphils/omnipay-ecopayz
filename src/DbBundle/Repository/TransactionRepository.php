<?php

namespace DbBundle\Repository;

use AppBundle\Service\PaymentOptionService;
use DbBundle\Entity\PaymentOption;
use Doctrine\ORM\Query;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\User;
use DbBundle\Entity\Currency;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\Product;
use Doctrine\ORM\QueryBuilder;

/**
 * TransactionRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TransactionRepository extends BaseRepository
{
    public function save($entity)
    {
        try {
            $this->getEntityManager()->beginTransaction();
            $this->getEntityManager()->persist($entity);
            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }

    public function findById($id, $hydrationMode = Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->join('t.customer', 'c');
        $qb->join('t.subTransactions', 's');
        $qb->join('s.customerProduct', 'cp');

        $qb->select('t,c,s,cp');
        $qb->where('t.id = :id')->setParameter('id', $id);

        return $qb->getQuery()->getSingleResult($hydrationMode);
    }

    public function findByReferenceNumber($referenceNumber, $hydrationMode = Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->join('t.customer', 'c');

        $qb->select('t,c');
        $qb->where('t.number = :referenceNumber')
            ->setParameter('referenceNumber', $referenceNumber);

        return $qb->getQuery()->getSingleResult($hydrationMode);
    }

    public function findByIdAndType($id, $type, $hydrationMode = Query::HYDRATE_OBJECT, $lockMode = null): Transaction
    {
        $qb = $this->createQueryBuilder('t');

        $qb->join('t.customer', 'c');
        $qb->leftJoin('c.user', 'u');

        // zimi-comment
        // $qb->join('t.subTransactions', 's');
        // $qb->join('s.customerProduct', 'cp');
        // $qb->join('cp.customer', 'cpc');p
        
        $qb->leftJoin('t.gateway', 'g');
        $qb->leftJoin('g.currency', 'gc');        
        $qb->select('t,c,g,gc,u');
        $qb->where('t.id = :id AND t.type = :type')->setParameter('id', $id)->setParameter('type', $type);        
        $qu = $qb->getQuery();
            
        if (!is_null($lockMode)) {
            $qu->setLockMode($lockMode);
        }

        // zimi          
        return $qu->getSingleResult($hydrationMode);               
    }

    /**
     * Create Query Builder.
     *
     * @param array | null $filters
     *
     * @return Doctrine/ORM/EntityRepository
     */
    public function getListQb($filters)
    {
        $qb = $this->createQueryBuilder('t');
        $qb->innerJoin('t.customer', 'c');
        $qb->leftJoin('t.currency', 'cu');

        if (isset($filters['search'])) {
            $qb->andWhere($qb->expr()->orX()->addMultiple([
                't.number LIKE :search',
            ]))->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (array_has($filters, 'types')) {
            $types = array_get($filters, 'types', []);
            if (!is_array($types)) {
                $types = [$types];
            }
            $qb->andWhere('FIND_IN_SET(t.type, :types) <> 0')->setParameter('types', implode(',', $types));
        }

        return $qb;
    }

    public function getList($filters = null)
    {
        $qb = $this->getListQb($filters);
        $qb->select(
            'PARTIAL t.{id,number, customer, currency, amount, type, date, status, isVoided, details, gateway}',
            'PARTIAL c.{id, fName, mName, lName, pinUserCode}',
            'PARTIAL cu.{id, code, name}'
        );

        if (isset($filters['length'])) {
            $qb->setMaxResults($filters['length']);
        }
        if (isset($filters['start'])) {
            $qb->setFirstResult($filters['start']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function getListFilterCount($filters = null)
    {
        $qb = $this->getListQb($filters);
        $qb->select('COUNT(t.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getListAllCount()
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('COUNT(t.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function updateTransactionPreference($key = null, $params = [])
    {
        $status = false;
        if (empty($key)) {
            return $status;
        }
        /* //should be like these
        $param = array(
            'key' => 'isRead',
            'id' => 'id' or NULL
        );
        */

        $qb = $this->createQueryBuilder('t');
        $qb
            ->update('DbBundle:Transaction', 't')
            ->set('t.details', 'JSON_REMOVE(t.details, :key)')
            ->setParameter('key', ['$.' . $key])
        ;

        if (isset($params['type'])) {
            $qb
                ->andWhere('t.type in (:type)')
                ->setParameter('type', $params['type'])
            ;
        }

        if (isset($params['id'])) {
            $qb
                ->andWhere('t.id = :id')
                ->setParameter('id', $params['id'])
            ;
        }

        $qb->getQuery()->execute();
        $status = true;

        return $status;
    }

    public function findTransactions($filters = [], $orders = [], $limit = 10, $offset = 0, $select = [], $hydrationMode = Query::HYDRATE_OBJECT): array
    {

        $queryBuilder = $this->createFilterQueryBuilder($filters);
        $queryBuilder->setMaxResults($limit);
        $queryBuilder->setFirstResult($offset);

        $queryBuilder->select($select);
     
        foreach ($orders as $order) {
            $queryBuilder->addOrderBy($order['column'], $order['dir']);
        }

        return $queryBuilder->getQuery()->getResult($hydrationMode);
    }

    public function getTransactionsForExportQuery($filters = [], $orders = [], $limit = 10, $offset = 0): Query
    {
        $queryBuilder = $this->createFilterQueryBuilder($filters);
        $queryBuilder->setMaxResults($limit);
        $queryBuilder->setFirstResult($offset);
        $queryBuilder->leftJoin(User::class, 'createdBy','WITH','transaction.createdBy = createdBy.id');
        $queryBuilder->leftJoin(Currency::class, 'currency','WITH','transaction.currency = currency.id');

        // customerProductData ata for p2p and transfer transactions
        $queryBuilder->leftJoin(SubTransaction::class, 'withdrawal_subtransaction','WITH','transaction.id = withdrawal_subtransaction.parent AND withdrawal_subtransaction.type = '. Transaction::TRANSACTION_TYPE_WITHDRAW);
        $queryBuilder->leftJoin(CustomerProduct::class, 'withdrawal_customerProduct','WITH','withdrawal_subtransaction.customerProduct = withdrawal_customerProduct.id');
        $queryBuilder->leftJoin(Product::class, 'withdrawal_product','WITH','withdrawal_customerProduct.product = withdrawal_product.id');

        $queryBuilder->leftJoin(SubTransaction::class, 'subtransaction','WITH','transaction.id = subtransaction.parent AND subtransaction.type != '. Transaction::TRANSACTION_TYPE_WITHDRAW);
        $queryBuilder->leftJoin(CustomerProduct::class, 'customerProduct','WITH','subtransaction.customerProduct = customerProduct.id');
        $queryBuilder->leftJoin(Product::class, 'product','WITH','customerProduct.product = product.id');

        $queryBuilder->select('transaction.id as transactionId, transaction.number as number, transaction.date as date, user.username as customerUsername, customer.fullName as customerFullName, transaction.amount as amount,transaction.status as statusId, transaction.type as typeId, transaction.isVoided');
        $queryBuilder->addSelect('IFNULL(JSON_UNQUOTE(JSON_EXTRACT(transaction.fees, \'$.total_company_fee\')), 0) as companyFee');
        $queryBuilder->addSelect('IFNULL(JSON_UNQUOTE(JSON_EXTRACT(transaction.fees, \'$.total_customer_fee\')), 0) as memberFee');
        $queryBuilder->addSelect("CONCAT(
            JSON_UNQUOTE(JSON_EXTRACT(transaction.details, '$.paymentOptionOnTransaction.code')),
            '(' ,
                    CASE
                        WHEN 
                            JSON_UNQUOTE(JSON_EXTRACT(transaction.details, '$.paymentOptionOnTransaction.email')) != '' AND
                            JSON_UNQUOTE(JSON_EXTRACT(transaction.details, '$.paymentOptionOnTransaction.email')) != 'null'
                        THEN 
                            JSON_UNQUOTE(JSON_EXTRACT(transaction.details, '$.paymentOptionOnTransaction.email'))
                        WHEN 
                            JSON_UNQUOTE(JSON_EXTRACT(transaction.details, '$.paymentOptionOnTransaction.account_id')) != '' OR
                            JSON_UNQUOTE(JSON_EXTRACT(transaction.details, '$.paymentOptionOnTransaction.account_id')) != 'null'
                        THEN 
                            JSON_UNQUOTE(JSON_EXTRACT(transaction.details, '$.paymentOptionOnTransaction.account_id'))
                        ELSE ''
                     END
                ,
            ')'
            ) as immutablePaymentOptionDataOnTransaction");
        $queryBuilder->addSelect('IF (createdBy.type = '. User::USER_TYPE_MEMBER .',true,false) as wasCreatedFromAms');
        $queryBuilder->addSelect('currency.code as currencyCode');
        $queryBuilder->addSelect("
        CONCAT(
            GROUP_CONCAT(
                CONCAT(
                    IFNULL(withdrawal_product.name, ''),
                    ' (',
                    IFNULL(withdrawal_customerProduct.userName, '')
                    ,') '
                ) SEPARATOR ', '),
            ' ',
            GROUP_CONCAT(
                CONCAT(
                    IFNULL(product.name, ''),
                    ' (',
                    IFNULL(customerProduct.userName, ''),
                    ')'
                 ) SEPARATOR ', '
            )
        ) as productsAndUsernames");
        $queryBuilder->groupBy('transactionId');

        foreach ($orders as $order) {
            $queryBuilder->addOrderBy($order['column'], $order['dir']);
        }
        return $queryBuilder->getQuery();
    }

    public function getTotal($filters = []): int
    {
        if (empty($filters)) {
            $queryBuilder = $this->createQueryBuilder('transaction');
        } else {
            $queryBuilder = $this->createFilterQueryBuilder($filters);
        }
        $queryBuilder->select('COUNT(transaction.id)');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getTotalTransactionPerStatuses(array $statuses): array
    {
        $total = [];
        foreach ($statuses as $status => $statusConditions) {
            $total['status_' . $status] = $this->getTotalTransactionPerStatus($status, $statusConditions['conditions'], $statusConditions['params']);
        }

        return $total;
    }

    public function getTotalTransactionPerStatus(string $status, array $conditions = [], array $conditionParams = []): int
    {
        $queryBuilder = $this->createQueryBuilder('transaction');
        $queryBuilder->select('COUNT(transaction.id) total');

        $sqlCondition = ['transaction.isVoided = :isVoided'];
        if ($status === 'voided') {
            $queryBuilder->setParameter('isVoided', true);
        } else {
            $sqlCondition[] = 'transaction.status = :status';
            $queryBuilder->setParameter('status', $status);
            $queryBuilder->setParameter('isVoided', false);
        }

        $sqlCondition = array_merge($sqlCondition, $conditions);

        foreach ($sqlCondition as $condition) {
            $queryBuilder->andWhere($condition);
        }

        foreach ($conditionParams as $param => $value) {
            $queryBuilder->setParameter($param, $value);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getLatestCreatedTransactions($limit = 10, $offset = 0): array
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('t.id', 't.number', 't.status', 't.type', 't.createdAt');
        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);
        $qb->andWhere('t.status = :status');
        $qb->setParameter('status', Transaction::TRANSACTION_STATUS_START);
        $qb->orderBy('t.createdAt', 'desc');

        return $qb->getQuery()->getResult();
    }

    public function getTotalMemberDepositTransactionWithTypeAndStatus(
        int $memberId,
        int $status,
        string $paymentMode
    ): int {
        $queryBuilder = $this->createQueryBuilder('transaction');
        $queryBuilder
            ->select('COUNT(transaction.id) totalTransaction')
            ->innerJoin('transaction.paymentOptionType', 'paymentOptionType')
            ->where($queryBuilder->expr()->andX()->addMultiple([
                'transaction.customer = :memberId',
                'transaction.status = :status',
                'paymentOptionType.paymentMode = :paymentMode'
            ]))
            ->setParameters([
                'memberId' => $memberId,
                'status' => $status,
                'paymentMode' => $paymentMode,
            ]);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getTransactionsToDecline(string $interval, int $status, int $type, array $paymentOptionType): array
    {
        $dateInterval = new \DateTime("-" . $interval);
        $dateInterval->setTimezone(new \DateTimeZone('UTC'));

        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder
            ->select('t.id')
            ->where('t.status = :status AND t.isVoided = 0')
            ->andWhere('t.updatedAt <= :interval')
            ->andWhere('t.type = :type')
            ->andWhere('t.paymentOptionType IN (:paymentOptionType)')
            ->andWhere("JSON_EXTRACT(t.details, '$.bitcoin.confirmation_count') IS NULL")
            ->setParameter('interval', $dateInterval)
            ->setParameter('status', $status)
            ->setParameter('type', $type)
            ->setParameter('paymentOptionType', $paymentOptionType);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * @param \DateTimeImmutable $expiration
     * @param int $status
     * @param array $types
     * @return Transaction[]
     */
    public function getTransactionsByStatusAndType(array $statuses, array $types, array $paymentOptionCodes): array
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder
            ->select('t')
            ->where($queryBuilder->expr()->andX()->addMultiple([
                't.status IN (:statuses)',
                't.isVoided = 0',
                't.type IN (:types)',
                't.paymentOptionType IN (:paymentOptionCodes)'
            ]))
            ->setParameters([
                'statuses' => $statuses,
                'types' => $types,
                'paymentOptionCodes' => $paymentOptionCodes,
            ])
        ;

        return $queryBuilder->getQuery()->getResult();
    }

    public function getBitcoinTransactionsToLock(string $interval): array
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder
            ->select('t.id')
            ->where('t.status = :status AND t.isVoided = 0')
            ->andWhere('t.updatedAt <= :interval')
            ->andWhere('t.type = :type')
            ->andWhere('t.paymentOptionType IN (:paymentOptionType)')
            ->andWhere("JSON_CONTAINS(t.details, 'false', '$.bitcoin.rate_expired') = true")
            ->setParameter('interval', new \DateTime("-" . $interval))
            ->setParameter('status', Transaction::TRANSACTION_STATUS_START)
            ->setParameter('type', Transaction::TRANSACTION_TYPE_DEPOSIT)
            ->setParameter('paymentOptionType', ['BITCOIN']);
                     
        return $queryBuilder->getQuery()->getArrayResult();
    }

    private function createFilterQueryBuilder($filters, $joins = []): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('transaction');

        if ($this->validFilter($filters, 'excludeStatus') || $this->validFilter($filters, 'search') || $this->validFilter($filters, 'isUsingExport')) {
            $this->queryBuilderJoin($queryBuilder, 'transaction.customer', 'customer');
            $this->queryBuilderJoin($queryBuilder, 'customer.user', 'user');
        }

        foreach ($joins as $key => $alias) {
            $this->queryBuilderJoin($queryBuilder, $key, $alias);
        }

        if (!empty(array_get($filters, 'source', []))) {
            $queryBuilder->join('transaction.creator', 'creator');
            $sources = [];
            foreach ($filters['source'] as $source) {
                if ($source === 'admin') {
                    $sources[] = User::USER_TYPE_ADMIN;
                } elseif ($source === 'member') {
                    $sources[] = User::USER_TYPE_MEMBER;
                }
            }
            $queryBuilder->andWhere('creator.type IN (:sources)');
            $queryBuilder->setParameter('sources', $sources);
        }

        if ($this->validFilter($filters, 'customer')) {
            $queryBuilder->andWhere('(transaction.customer = :customer OR transaction.toCustomer = :customer)');
            $queryBuilder->setParameter('customer', $filters['customer']);
        }

        if ($this->validFilter($filters, 'from')) {
            $queryBuilder->andWhere('transaction.date >= :from');
            $queryBuilder->setParameter('from', new \DateTime($filters['from']));
        }

        if ($this->validFilter($filters, 'to', '')) {
            $queryBuilder->andWhere('transaction.date < :to');
            $queryBuilder->setParameter('to', (new \DateTime($filters['to'] . '+1 day')));
        }

        if ($this->validFilter($filters, 'product', '')) {
            if (!empty(array_get($filters, 'product', []))) {
                $queryBuilder->andWhere(
                    '(SELECT COUNT(st.id) FROM ' . SubTransaction::class . ' AS st '
                    . 'INNER JOIN st.customerProduct AS stp WHERE st.parent = transaction AND stp.productID IN (:product)) > 0'
                );
                $queryBuilder->setParameter('product', $filters['product']);
            }
        }

        if ($this->validFilter($filters, 'gateways', [])) {
            $queryBuilder->andWhere('transaction.gateway IN (:gateways)')->setParameter('gateways', $filters['gateways']);
        }

        if ($this->validFilter($filters, 'search', '')) {
            $searchValue = array_get($filters, 'search');
            $exp = $queryBuilder->expr()->orX();
            $exp->add("transaction.number LIKE :search");

            if (isset($filters['searchCustomerIds']) && !empty(array_get($filters['searchCustomerIds'], 0))) {
                $exp->add("transaction.customer IN (:searchCustomerIds)");
                $queryBuilder->setParameter('searchCustomerIds', $filters['searchCustomerIds']);
            }
            if (isset($filters['searchCustomerIds'], $filters['searchTransactionIds']) && !empty(array_get($filters['searchCustomerIds'], 0)) && !empty(array_get($filters['searchTransactionIds'], 0))) {
                $exp->add("transaction.id IN (:searchTransactionIds)");
                $queryBuilder->setParameter('searchTransactionIds', $filters['searchTransactionIds']);
            } elseif (isset($filters['searchTransactionIds']) && !empty(array_get($filters['searchTransactionIds'], 0))) {
                $exp->add("transaction.id IN (:searchTransactionIds)");
                $queryBuilder->setParameter('searchTransactionIds', $filters['searchTransactionIds']);
            }

            $exp->add('transaction.virtualBitcoinReceiverUniqueAddress LIKE :receiverAddress ');
            $exp->add('transaction.virtualBitcoinTransactionHash = :bitcoinTransactionHash');

            $queryBuilder
                ->andWhere($exp)
                ->setParameter('search', $searchValue . '%')
                ->setParameter('bitcoinTransactionHash', $searchValue)
                ->setParameter('receiverAddress', '%'. $searchValue . '%')
            ;
        }

        if ($this->validFilter($filters, 'type')) {
            $queryBuilder->andWhere('transaction.type = :type')->setParameter('type', $filters['type']);
        }

        if (!empty(array_get($filters, 'types', []))) {
            $exp = $queryBuilder->expr()->orX();
            foreach ($filters['types'] as $i => $type) {
                $exp->add('transaction.type = :type_' . $i);
                $queryBuilder->setParameter('type_' . $i, $this->getTypeValue($type));
            }
            $queryBuilder->andWhere($exp);
        }

        // for handling default pending filter value
        if ($this->validFilter($filters, 'excludeStatus')) {
            if(empty(array_get($filters, 'status', []))){
                //no additional filter on status
                $filters['status'] = array(Transaction::TRANSACTION_STATUS_START, Transaction::TRANSACTION_STATUS_ACKNOWLEDGE, Transaction::DETAIL_BITCOIN_STATUS_PENDING);
            }
        }

        if (!empty(array_get($filters, 'status', []))) {
            $exp = $queryBuilder->expr()->orX();

            if (is_array($filters['status'])) {
                if (in_array(Transaction::TRANSACTION_STATUS_VOIDED, $filters['status'])) {
                    $exp->add('transaction.isVoided = true');
                }

                if (in_array(Transaction::DETAIL_BITCOIN_STATUS_PENDING, $filters['status'])) {
                    $exp->add('transaction.paymentOptionType = :bitcoin
                                AND transaction.type = :deposit
                                AND transaction.status != :endStatus
                                AND transaction.isVoided = 0
                                AND IFNULL(transaction.bitcoinConfirmationCount, 0) < :bitcoinConfirmedCount');

                    $queryBuilder->setParameter('bitcoin', PaymentOption::PAYMENT_MODE_BITCOIN)
                        ->setParameter('endStatus', Transaction::TRANSACTION_STATUS_END)
                        ->setParameter('deposit', Transaction::TRANSACTION_TYPE_DEPOSIT)
	                    ->setParameter('bitcoinConfirmedCount', Transaction::BITCOIN_CONFIRMED_COUNT);
                }

                if (in_array(Transaction::DETAIL_BITCOIN_STATUS_CONFIRMED, $filters['status'])) {
                    $exp->add('transaction.paymentOptionType = :bitcoin
                                AND transaction.type = :deposit
                                AND transaction.bitcoinConfirmationCount IS NOT NULL
                                AND transaction.status != :endStatus
                                AND transaction.isVoided = 0
                                AND transaction.bitcoinConfirmationCount >= :bitcoinConfirmedCount');

                    $queryBuilder->setParameter('bitcoin', PaymentOption::PAYMENT_MODE_BITCOIN)
                        ->setParameter('endStatus', Transaction::TRANSACTION_STATUS_END)
                        ->setParameter('deposit', Transaction::TRANSACTION_TYPE_DEPOSIT)
	                    ->setParameter('bitcoinConfirmedCount', Transaction::BITCOIN_CONFIRMED_COUNT);
                }
            }

            $exp->add('transaction.status IN (:status) AND transaction.isVoided = false');
            $queryBuilder->setParameter('status', $filters['status']);
            $queryBuilder->andWhere($exp);
        }

        if ($this->validFilter($filters, 'excludeStatus')) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->notIn('transaction.status', ':excludeStatus')
            )->setParameter('excludeStatus', $filters['excludeStatus']);
        } else {
            if ($this->validFilter($filters, 'isDataTableTransaction')) {
                $queryBuilder
                    ->andWhere('transaction.status NOT IN (:transactionStatus)')
                    ->setParameter('transactionStatus', Transaction::getPendingStatus());
            }
        }

        if ($this->validFilter($filters, 'voided')) {
            $queryBuilder->andWhere('transaction.isVoided = :voided')->setParameter('voided', $filters['voided']);
        }

        if ($this->validFilter($filters, 'paymentOption')) {
            $queryBuilder->andWhere('transaction.paymentOptionType IN (:paymentOption)')->setParameter('paymentOption', $filters['paymentOption']);
        }

        if ($this->validFilter($filters, 'interval')) {
            $queryBuilder->andWhere('transaction.date <= CURRENT_TIMESTAMP() AND transaction.date >= :interval');
            $queryBuilder->setParameter('interval', new \DateTime("-" . $filters['interval']));
        }

        if (array_has($filters, 'customerId')) {
            $queryBuilder
                ->andWhere('transaction.customer = :customerId OR transaction.toCustomer = :customerId')
                ->setParameter('customerId', $filters['customerId'])
            ;
        }

        return $queryBuilder;
    }

    private function queryBuilderJoin(QueryBuilder $queryBuilder, string $key, string $alias)
    {
        $joinDqlPart = $queryBuilder->getDQLParts()['join'];
        $aliasAlreadyExists = false;
        foreach ($joinDqlPart as $joins) {
            foreach ($joins as $join) {
                if ($join->getAlias() === $alias) {
                    $aliasAlreadyExists = true;

                    break 2;
                }
            }
        }

        if ($aliasAlreadyExists === false) {
            $queryBuilder->join($key, $alias);
        }
    }

    private function validFilter($filters, $name): bool
    {
        if (array_get($filters, $name, '') === '' || array_get($filters, $name, '') === null) {
            return false;
        }

        return true;
    }

    private function getTypesValue(): array
    {
        return [
            'deposit' => Transaction::TRANSACTION_TYPE_DEPOSIT,
            'withdraw' => Transaction::TRANSACTION_TYPE_WITHDRAW,
            'transfer' => Transaction::TRANSACTION_TYPE_TRANSFER,
            'p2ptransfer' => Transaction::TRANSACTION_TYPE_P2P_TRANSFER,
            'bonus' => Transaction::TRANSACTION_TYPE_BONUS,
            'commission' => Transaction::TRANSACTION_TYPE_COMMISSION,
            'revenue_share' => Transaction::TRANSACTION_TYPE_REVENUE_SHARE,
            'debit_adjustment' => Transaction::TRANSACTION_TYPE_DEBIT_ADJUSTMENT,
            'credit_adjustment' => Transaction::TRANSACTION_TYPE_CREDIT_ADJUSTMENT,
        ];
    }

    private function getTypeValue(string $type): int
    {
        return $this->getTypesValue()[$type];
    }

    public function findLastTransactionDateByMemberId(int $memberId): ?\DateTimeInterface
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('MAX(t.date) as lastTransactionDate');
        $qb->where('t.customer = :memberId')->setParameter('memberId', $memberId);

        $lastTransactionDate = $qb->getQuery()->getSingleScalarResult();

        if (!is_null($lastTransactionDate)) {
            return new \DateTimeImmutable($lastTransactionDate);
        }
        return null;
    }

    public function getLessThanConfirmationBitcoinTransactionForMember(int $memberId, int $confirmation): Transaction
    {
        $qb = $this->createQueryBuilder('transaction');
        $qb
            ->select('transaction, m')
            ->innerJoin('transaction.customer', 'm')
            ->where($qb->expr()->andX()->addMultiple([
                'transaction.customer = :memberId',
                'transaction.type = :type',
                'transaction.paymentOptionType  = :paymentOptionType',
                'transaction.status = :status',
                'transaction.isVoided = false'
            ]))
            ->andWhere($qb->expr()->orX()->addMultiple([
                'transaction.bitcoinConfirmation < :confirmation',
                'transaction.bitcoinConfirmation IS NULL'
            ]))
            ->setParameters([
                'memberId' => $memberId,
                'confirmation' => $confirmation,
                'type' => Transaction::TRANSACTION_TYPE_DEPOSIT,
                'paymentOptionType' => 'BITCOIN',
                'status' => Transaction::TRANSACTION_STATUS_START,
            ])
            ->setMaxResults(2)
        ;

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @param int $memberId
     * @param string $paymentOption
     * @param $transactionType
     * @return Transaction
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLastTransactionForPaymentOption(int $memberId, string $paymentOption, int $transactionType): Transaction
    {
        $query = $this->createQueryBuilder('t')
            ->select('t')
            ->where('t.customer = :memberId AND t.paymentOptionType = :paymentOption AND t.isVoided = false AND t.status NOT IN (:statuses)')
            ->andWhere('t.type = :transactionType')
            ->orderBy('t.id', 'DESC')
            ->setMaxResults(1)
            ->setParameters([
                'memberId' => $memberId,
                'statuses' => [Transaction::TRANSACTION_STATUS_DECLINE, Transaction::TRANSACTION_STATUS_END],
                'paymentOption' => $paymentOption,
                'transactionType' => $transactionType
            ])
        ;

        if ($transactionType === Transaction::TRANSACTION_TYPE_WITHDRAW) {
            $query->andWhere('t.bitcoinIsAcknowledgeByMember <> TRUE');
        }

        return $query->getQuery()->getSingleResult();
    }

    public function findUserUnacknowledgedDepositBitcoinTransaction(int $memberId): ?Transaction
    {
        $queryBuilder = $this->createQueryBuilder('transaction');
        $queryBuilder
            ->select('transaction')
//            ->innerJoin('transaction.paymentOptionType', 'paymentOptionType')
            ->where('transaction.customer = :customer')
            ->andWhere('transaction.type = 1')
            ->andWhere('transaction.status NOT IN (:status)')
            ->andWhere('transaction.isVoided != true')
            ->andWhere('transaction.paymentOptionType = :paymentOptionType')
            ->andWhere("JSON_CONTAINS(transaction.details, 'false', '$.bitcoin.acknowledged_by_user') = true")
            ->setParameter('customer', $memberId)
            ->setParameter('paymentOptionType', PaymentOptionService::BITCOIN)
            ->setParameter('status', [Transaction::TRANSACTION_STATUS_END, Transaction::TRANSACTION_STATUS_DECLINE])
            ->setMaxResults(1)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

	public function getTotalProcessedDepositTransactionsForEachPaymentOption($customerId)
	{
		$queryBuilder = $this->createQueryBuilder('transaction');

		return $queryBuilder
			->select('COUNT(transaction) as count', 'transaction.paymentOptionType')
			->where($queryBuilder->expr()->andX()->addMultiple([
				'transaction.customer = :customer',
				'transaction.type = :type',
				'transaction.status = :status',
			]))
			->groupBy('transaction.paymentOptionType')
			->setParameters([
				'customer' => $customerId,
				'type' => Transaction::TRANSACTION_TYPE_DEPOSIT,
				'status' => Transaction::TRANSACTION_STATUS_END
			])
			->getQuery()
			->getResult();
	}
}
