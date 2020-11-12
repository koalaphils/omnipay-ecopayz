<?php

namespace DbBundle\Repository;

use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\Product;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

/**
 * CustomerProductRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CustomerProductRepository extends BaseRepository
{
    /**
     * @param int $id
     * @param int $hydrationMode
     *
     * @return type
     */
    public function findById($id, $hydrationMode = Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('cp');
        $qb->join('cp.product', 'p');
        $qb->leftJoin('cp.customer', 'c');
        $qb->leftJoin('c.currency', 'cu');
        $qb->select('PARTIAL cp.{id, userName, balance, isActive}, PARTIAL p.{id, name}, cu, c');
        $qb->where('cp.id = :id')->setParameter('id', $id);

        return $qb->getQuery()->getSingleResult($hydrationMode);
    }

    /**
     * @param int $username
     * @param int $hydrationMode
     *
     * @return type
     */
    public function findByUsername($username, $hydrationMode = Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('cp');
        $qb->join('cp.product', 'p');
        $qb->join('cp.customer', 'c');
        $qb->select('cp, p, c');
        $qb->where('cp.userName = :username')->setParameter('username', $username);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult($hydrationMode);
    }

    public function findByUsernameProductAndCurrency(string $username, int $product, int $currency): ?MemberProduct
    {
        $queryBuilder = $this->createQueryBuilder('cp');
        $queryBuilder
            ->join('cp.product', 'p')
            ->join('cp.customer', 'c')
            ->join('c.currency', 'cu')
            ->select('cp, p, c, cu')
            ->where('cp.userName = :username')
            ->andWhere('p.id = :product')
            ->andWhere('cu.id = :currency')
            ->setParameters([
                'username' => $username,
                'product' => $product,
                'currency' => $currency,
            ])
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findByUsernameProductCodeAndCurrencyCode(string $username, string $product, string $currency): ?MemberProduct
    {
        $queryBuilder = $this->createQueryBuilder('cp');
        $queryBuilder
            ->join('cp.product', 'p')
            ->join('cp.customer', 'c')
            ->join('c.currency', 'cu')
            ->select('cp, p, c, cu')
            ->where('cp.userName = :username')
            ->andWhere('p.code = :product')
            ->andWhere('cu.code = :currency')
            ->setParameters([
                'username' => $username,
                'product' => $product,
                'currency' => $currency,
            ])
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findByUsernameProduct(string $username, int $product): ?MemberProduct
    {
        $queryBuilder = $this->createQueryBuilder('cp');
        $queryBuilder
            ->join('cp.product', 'p')
            ->join('cp.customer', 'c')
            ->select('cp, p, c')
            ->where('cp.userName = :username')
            ->andWhere('p.id = :product')
            ->setParameters([
                'username' => $username,
                'product' => $product,
            ])
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findByidAndCustomer($id, $customerId, $hydrationMode = Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('cp');
        $qb->join('cp.product', 'p');
        $qb->select('cp, p');
        $qb->where('cp.id = :id')->setParameter('id', $id);
        $qb->andWhere('cp.customer = :customerId')->setParameter('customerId', $customerId);

        return $qb->getQuery()->getSingleResult($hydrationMode);
    }

    public function findOneByCustomerAndProductCode(Member $member, string $code): ?MemberProduct
    {
        $qb = $this->createQueryBuilder('cp');
        $qb->join('cp.product', 'p');
        $qb->select('cp, p');
        $qb->where('p.code = :code')->setParameter('code', $code);
        $qb->andWhere('cp.customer = :customer')->setParameter('customer', $member);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getMemberPiwiMemberWallet(Member $member, $wallet = Product::MEMBER_WALLET_CODE): ?MemberProduct
    {
        return $this->findOneByCustomerAndProductCode($member, $wallet);
    }

    public function getPinnacleProduct(Member $member): ?MemberProduct
    {
        return $this->findOneByCustomerAndProductCode($member, 'PINBET');
    }

    /**
     * Create Query Builder.
     *
     * @param array | null $filters
     *
     * @return Doctrine/ORM/QueryBuilder
     */
    public function getCustomerProductListQb($filters)
    {
        $qb = $this
            ->createQueryBuilder('cp')
            ->select('PARTIAL cp.{id, userName, balance, isActive, details}, PARTIAL p.{id, name, details, deletedAt}, c')
            ->leftJoin('cp.customer', 'c')
            ->leftJoin('cp.product', 'p')
            ->orderBy('cp.createdAt', 'DESC')
        ;

        if (isset($filters['customerID'])) {
            if (!is_array($filters['customerID'])) {
                $filters['customerID'] = [$filters['customerID']];
            }
            if (!empty($filters['customerID'])) {
                $qb->andWhere($qb->expr()->andX('cp.customerID = :customerID'))->setParameter('customerID', $filters['customerID']);
            }
        }

        if (isset($filters['products']) && !empty($filters['products'])) {
            $qb->andWhere('p.id IN (:products)')->setParameter('products', $filters['products']);
        }

        if (isset($filters['currencies']) && !empty($filters['currencies'])) {
            $qb->andWhere('c.currency IN (:currencies)')->setParameter('currencies', $filters['currencies']);
        }

        if (isset($filters['ids'])) {
            if (!is_array($filters['ids'])) {
                $filters['ids'] = [$filters['ids']];
            }
            $qb->andWhere('cp.id IN (:ids)')->setParameter('ids', $filters['ids']);
        }

        if (array_get($filters, 'isActive', false) == true) {
            $qb->andWhere('cp.isActive = 1');
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $qb->andWhere($qb->expr()->orX()->addMultiple([
                'p.name LIKE :search',
                'cp.userName LIKE :search',
                'cp.balance LIKE :search',
            ]))->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb;
    }

    public function getCustomerProductList(array $filters = [], array $orders = [], int $limit = 20, int $offset = 0): array
    {
        $qb = $this->getCustomerProductListQb($filters);

        if (!empty($orders)) {
            foreach ($orders as $column => $dir) {
                $qb->addOrderBy($column, $dir);
            }
        }

        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);

        return $qb->getQuery()->getArrayResult();
    }

    public function getCustomerProductListInNative(array $filters = [], array $orders = [], int $limit = 20, int $offset = 0): array
    {
        $entityManager = $this->getEntityManager();
        $connection = $entityManager->getConnection();
        $queryBuilder = $connection->createQueryBuilder();

        $queryBuilder
            ->select("cp.cproduct_id id, cp.cproduct_username userName, cp.cproduct_balance balance, 
                cp.cproduct_is_active isActive, cp.cproduct_requested_at requestedAt, cp.cproduct_details details,"
                . "c.customer_id, "
                . "p.product_id, p.product_name, p.product_details, p.product_code")
            ->from("customer_product", "cp")
            ->leftJoin("cp", "product", "p", "cp.cproduct_product_id = p.product_id ")
            ->leftJoin("cp", "customer", "c", "cp.cproduct_customer_id = c.customer_id")
        ;

        if (isset($filters['customerID'])) {
            if (!empty($filters['customerID'])) {
                $queryBuilder
                    ->andWhere($queryBuilder->expr()->andX('cp.cproduct_customer_id = :customerID'))
                    ->setParameter('customerID', $filters['customerID'])
                ;
            }
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $queryBuilder->andWhere($queryBuilder->expr()->orX()->addMultiple([
                'p.product_name LIKE :search',
                'cp.cproduct_username LIKE :search',
                'cp.cproduct_balance LIKE :search',
            ]))->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($orders)) {
            if (!empty($orders)) {
                foreach ($orders as $column => $dir) {
                    $queryBuilder->addOrderBy($column, $dir);
                }
            }
        } else {
            $queryBuilder->orderBy("CASE cp.cproduct_is_active WHEN 0 THEN cp.cproduct_updated_at END", "ASC");
            $queryBuilder->addOrderBy("CASE cp.cproduct_is_active WHEN 1 THEN cp.cproduct_updated_at END", "DESC");
        }
        $queryBuilder->setMaxResults($limit);
        $queryBuilder->setFirstResult($offset);

        $statement = $queryBuilder->execute();

        return $statement->fetchAll();
    }

    public function getCustomerProductListFilterCount($filters = null)
    {
        $qb = $this->getCustomerProductListQb($filters);
        $qb->select('COUNT(cp.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getCustomerProductListAllCount()
    {
        $qb = $this->createQueryBuilder('cp');
        $qb->select('COUNT(cp.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findByCodeAndUsername($product)
    {
        $qb = $this->createQueryBuilder('cp');

        $qb->select('cp')
            ->join('cp.product', 'p', 'WITH', $qb->expr()->eq('p.code', ':code'))
            ->where($qb->expr()->eq('cp.userName', ':username'))
            ->setParameter('code', $product['code'])
            ->setParameter('username', $product['username']);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getSummaryTotal($filters): array
    {
        $queryBuilder = $this->getCustomerProductListQb($filters);
        $queryBuilder->select(
            'COUNT(cp.id) totalCustomerProduct',
            'COUNT(DISTINCT cp.customer) totalCustomer',
            'SUM(CAST(cp.balance AS DECIMAL(65, 10))) totalBalances'
        );

        return $queryBuilder->getQuery()->getSingleResult(Query::HYDRATE_ARRAY);
    }

    public function getTotalCustomerProductByAffiliate(Member $affiliate, array $productIds = []): array
    {
        $queryBuilder = $this->createQueryBuilder('cp');
        $queryBuilder->join('cp.product', 'p');
        $queryBuilder->join('cp.customer', 'c', 'WITH', 'c.affiliate = :affiliate');
        $queryBuilder->join('c.currency', 'cu');
        $queryBuilder->select('p.id productId, cu.id currencyId, COUNT(DISTINCT c.id) totalCustomers, COUNT(cp.id) totalCustomerProducts');
        $queryBuilder->groupBy('p.id');
        $queryBuilder->addGroupBy('cu.id');
        $queryBuilder->setParameter('affiliate', $affiliate->getId());
        if (!empty($productIds)) {
            $queryBuilder->where('p.id in (:productIds)');
            $queryBuilder->setParameter('productIds', $productIds);
        }

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function getTotalActiveCustomerProductByAffiliate(Member $member, array $productIds = []): array
    {
        $queryBuilder = $this->createQueryBuilder('cp');
        $queryBuilder->join('cp.product', 'p');
        $queryBuilder->join('cp.customer', 'c', 'WITH', 'c.affiliate = :affiliate');
        $queryBuilder->select('p.id productId, COUNT(DISTINCT c.id) totalCustomers, COUNT(cp.id) totalCustomerProducts');
        $queryBuilder->groupBy('p.id');
        $queryBuilder->setParameter('affiliate', $member->getId());
        $queryBuilder->andWhere('cp.isActive = 1');
        if (!empty($productIds)) {
            $queryBuilder->andWhere('p.id in (:productIds)');
            $queryBuilder->setParameter('productIds', $productIds);
        }

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function getTotalActiveMemberProductByReferrer(Member $referrer): array
    {
        $queryBuilder = $this->createQueryBuilder('cp');
        $queryBuilder->join('cp.product', 'p');
        $queryBuilder->join('cp.customer', 'c', 'WITH', 'c.affiliate = :affiliate');
        $queryBuilder->select('COUNT(DISTINCT c.id) totalCustomers, COUNT(cp.id) totalCustomerProducts');
        $queryBuilder->setParameter('affiliate', $referrer->getId());
        $queryBuilder->andWhere('cp.isActive = 1');
	    $queryBuilder->andWhere('JSON_EXTRACT(p.details, \'$.ac_wallet\') is null');

        return $queryBuilder->getQuery()->getSingleResult();
    }

    public function getCustomerProducts(Member $customer): array
    {
        return $this->createQueryBuilder('cp')
            ->select('cp')
            ->where('cp.customer = :customer')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getResult()
        ;
    }

    public function hasSyncedWithCustomerProduct(string $syncId): bool
    {

        $count = $this->createQueryBuilder('cp')
            ->select('COUNT(cp) as ct')
            ->where('cp.betSyncId = :syncId')
            ->setParameter('syncId', $syncId)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
    }

    public function getSyncedMemberProduct(string $syncId): ?MemberProduct
    {
        return $this->createQueryBuilder('cp')
            ->select('cp', 'product')
            ->innerJoin('cp.product', 'product')
            ->where('cp.betSyncId = :syncId')
            ->setParameter('syncId', $syncId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getProductWalletByMember(int $memberId): ?MemberProduct
    {
        $queryBuilder = $this->createQueryBuilder('cp');
        $queryBuilder->join('cp.customer', 'c', 'WITH', 'c.id = :memberId')
            ->join('cp.product', 'p', 'WITH', 'JSON_CONTAINS(p.details, :piwiWalletTag) = 1')
            ->setParameter('memberId', $memberId)
            ->setParameter('piwiWalletTag', json_encode(['piwi_wallet' => true]));

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function getReferrerTotalReferredProducts(array $filters = [], $orders = [], int $limit = 20, int $offset = 0): array
    {
        $queryBuilder = $this->getReferrerReferredProductsQb($filters);
        $queryBuilder->select( 'p.id AS productId', 'p.name AS productName', 'COUNT(p.id) AS totalProductReferrals')
            ->groupBy('p.id');

        if (!empty($orders)) {
            foreach ($orders as $column => $dir) {
                $queryBuilder->addOrderBy($column, $dir);
            }
        }

        $queryBuilder->setMaxResults($limit);
        $queryBuilder->setFirstResult($offset);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function getReferrerReferredProductsCount(array $filters): int
    {
        $queryBuilder = $this->getReferrerReferredProductsQb($filters);
        $queryBuilder->select('COUNT(DISTINCT(p.id))');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function getReferrerReferredProductsQb(array $filters): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('cp');

        $queryBuilder
            ->join('cp.product', 'p', Join::WITH, 'JSON_CONTAINS(p.details, :acWalletTag) = 0')
            ->join('cp.customer', 'c')
            ->join('c.affiliate', 'a')
            ->setParameter('acWalletTag', json_encode(['ac_wallet' => true]));

        if (array_get($filters, 'member', null) !== null) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq('a.id', ':referrerId'));
            $queryBuilder->setParameter('referrerId', array_get($filters, 'member'));
        }

        return $queryBuilder;
    }

    public function getReferralCurrenciesByReferrer(int $referrerId): array
    {
        $queryBuilder = $this->createQueryBuilder('mp');

        $queryBuilder->select('currency.code AS currencyCode')
            ->join('mp.customer', 'c')
            ->join('c.currency', 'currency')
            ->join('c.affiliate', 'a', Join::WITH, $queryBuilder->expr()->eq('a.id', ':referrerId'))
            ->join('mp.product', 'p', Join::WITH, "JSON_EXTRACT(p.details, '$.ac_wallet') IS NULL")
            ->setParameter('referrerId', $referrerId)
            ->groupBy('currency.id');

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function getRequestList($limit = 10, $offset = 0, $hydrationMode = Query::HYDRATE_OBJECT): array
    {
        $queryBuilder = $this->createQueryBuilder('mp');

        $queryBuilder
            ->select('mp', 'p', 'm')
            ->join('mp.product', 'p')
            ->join('mp.customer', 'm')
            ->where($queryBuilder->expr()->isNotNull('mp.requestedAt'))
            ->orderBy('mp.requestedAt', 'DESC');

        $queryBuilder->setMaxResults($limit);
        $queryBuilder->setFirstResult($offset);

        return $queryBuilder->getQuery()->getResult($hydrationMode);
    }

    public function getProductCodeListOfMember(int $memberId): array
    {
        $queryBuilder = $this->createQueryBuilder('mp');

        $queryBuilder->select('DISTINCT (p.code) AS code', 'p.name')
            ->join('mp.customer', 'm')
            ->join('mp.product', 'p')
            ->where($queryBuilder->expr()->eq('mp.isActive', true))
            ->andWhere($queryBuilder->expr()->eq('m.id', $memberId));

        return $queryBuilder->getQuery()->getArrayResult();
    }
}
