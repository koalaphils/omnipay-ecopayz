<?php

namespace DbBundle\Repository;

use DateTime;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\User;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\MemberBanner;
use DbBundle\Entity\MemberReferralName;
use DbBundle\Entity\MemberWebsite;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;

class CustomerRepository extends BaseRepository
{
    /**
     * @param int $id
     * @param int $hydrationMode
     *
     * @return type
     */
    public function findById($id, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->join('c.user', 'u');
        $qb->join('c.currency', 'ccu');
        $qb->select(
            'c'
            . ', PARTIAL u.{username, id, email, isActive, activationCode, activationSentTimestamp, activationTimestamp, preferences, password, resetPasswordCode, resetPasswordSentTimestamp, type, phoneNumber}'
            . ', PARTIAL ccu.{id, name, code, rate}'
        );
        $qb->where('c.id = :id')->setParameter('id', $id);

        return $qb->getQuery()->getSingleResult($hydrationMode);
    }

    
    public function getById(int $memberId): Member
    {
        $qb = $this->createQueryBuilder('c');
        $qb->join('c.user', 'u');
        $qb->join('c.currency', 'ccu');
        $qb->leftjoin('c.groups', 'g');
        $qb->select(
            'PARTIAL c.{id, fName, mName, pinUserCode, pinLoginId, lName, fullName, country, currency, balance, socials, joinedAt, birthDate, socials, details, contacts, verifiedAt, isAffiliate, isCustomer, transactionPassword, files, riskSetting, tags, notifications}'
            . ', PARTIAL u.{username, id, email, isActive, activationSentTimestamp, activationTimestamp, preferences, password, resetPasswordCode, resetPasswordSentTimestamp}'
            . ', PARTIAL ccu.{id, name, code, rate}, g'
        );
        $qb->where('c.id = :id')->setParameter('id', $memberId);

        return $qb->getQuery()->getSingleResult();
    }

    public function findMembersByFullNamePartialMatch(string $queryString, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->join('c.user', 'u');
        $qb->select('c'
        );
        $qb->where('c.fullName LIKE :queryString');
        $qb->setParameter('queryString', '%'. $queryString . '%');

        return $qb->getQuery()->getResult($hydrationMode);
    }

    public function findRequesterByZendeskId($zendeskId, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->join('c.user', 'u');
        $qb->select('c,u');
        $qb->where('u.zendeskId = :id')->setParameter('id', $zendeskId);

        return $qb->getQuery()->getSingleResult($hydrationMode);
    }

    /**
     * Create Query Builder.
     *
     * @deprecated since version 1.1
     *
     * @param array | null $filters
     *
     * @return Doctrine/ORM/EntityRepository
     */
    public function getCustomerListQb(array $filters)
    {
        $groupFilters = [];
        if (array_has($filters, 'filter')) {
            $groupFilters = array_get($filters, 'filter');
        }

        $qb = $this->createFilterQueryBuilder($groupFilters);

        if (!empty(array_get($filters, 'excludeMemberId', ''))) {
            $qb->andWhere($qb->expr()->neq('c.id', ':excludeMemberId'));
            $qb->setParameter('excludeMemberId', $filters['excludeMemberId']);
        }

        if (array_has($filters, 'isAffiliate')) {
            $qb->andWhere('c.isAffiliate = :isAffiliate');
            $qb->setParameter('isAffiliate', array_get($filters, 'isAffiliate'));
        }

        if (array_has($filters, 'isCustomer')) {
            $qb->andWhere('c.isCustomer = :isCustomer');
            $qb->setParameter('isCustomer', array_get($filters, 'isCustomer'));
        }

        if (array_has($filters, 'affiliate')) {
            $affiliate = array_get($filters, 'affiliate');
            if ($affiliate instanceof \DbBundle\Entity\Customer) {
                $affiliate = $affiliate->getId();
            } elseif (is_array($affiliate)) {
                $affiliate = array_get($affiliate, 'id');
            }
            $qb->andWhere('c.affiliate = :affiliate');
            $qb->setParameter('affiliate', $affiliate);
        }

        if (!empty($search = trim(array_get($filters, 'search', ''))) || !empty(array_get($filters, 'filter.paymentOption', []))) {
            $exp = $qb->expr()->orX();

            $searchCategory = array_get($groupFilters, 'searchCategory', []);

            if (in_array('email', $searchCategory)) {
	            $exp->add("LOWER(u.email) LIKE LOWER(:search)");
            }

            if(in_array('contacts', $searchCategory)) {
	            $exp->add("COALESCE(JSON_SEARCH(LOWER(c.contacts), 'one', LOWER(:search))) <> 0");
            }

            if (in_array('fullname', $searchCategory)) {
	            $exp->add("LOWER(c.fullName) LIKE LOWER(:search)");
            }

            if (in_array('productUsername', $searchCategory)) {
	            $exp->add("(SELECT COUNT(cps) FROM " . CustomerProduct::class . " AS cps WHERE cps.customer = c.id AND LOWER(cps.userName) LIKE  LOWER(:search)) > 0");
            }

            if (in_array('referralDetails', $searchCategory)) {
	            $exp->add('(SELECT COUNT(cbn) FROM ' . MemberBanner::class . ' AS cbn WHERE cbn.member = c.id AND LOWER(cbn.campaignName) LIKE LOWER(:search)) > 0');
	            $exp->add('(SELECT COUNT(cwb) FROM ' . MemberWebsite::class . ' AS cwb WHERE cwb.member = c.id AND LOWER(cwb.website) LIKE LOWER(:search)) > 0');
	            $exp->add('(SELECT COUNT(crn) FROM ' . MemberReferralName::class . ' AS crn WHERE crn.member = c.id AND LOWER(crn.name) LIKE LOWER(:search)) > 0');
            }

            if (in_array('trackingCode', $searchCategory)) {
	            $exp->add("LOWER(JSON_EXTRACT(u.preferences, '$.affiliateCode')) LIKE LOWER(:search)");
            }

            if (in_array('username', $searchCategory)) {
	            $exp->add("LOWER(u.username) LIKE LOWER(:search)");
            }

            if (in_array('userPreferences', $searchCategory)){
	            $exp->add("COALESCE(JSON_SEARCH(LOWER(u.preferences), 'one', LOWER(:search))) <> 0");
            }

	        if (empty($searchCategory) && !empty(trim($search))) {
		        $exp->add("LOWER(u.username) LIKE LOWER(:search)");
		        $exp->add("LOWER(c.fullName) LIKE LOWER(:search)");
		        $exp->add("LOWER(u.email) LIKE LOWER(:search)");
		        $exp->add("(SELECT COUNT(cps) FROM " . CustomerProduct::class . " AS cps WHERE cps.customer = c.id AND LOWER(cps.userName) LIKE LOWER(:search)) > 0");
	        }

	        if ($exp->count()) {
		        $qb->setParameter('search', '%' . $search . '%');
	        }

	        //payment option filter/search
	        if(array_has($filters, 'customer_payment_options.customer')) {
		        $customerIds = array_get($filters, 'customer_payment_options.customer', []);
		        if(!empty(array_get($filters, 'filter.paymentOption', []))){
			        $qb->andWhere('c.id IN (:ids)');
			        $qb->setParameter('ids', $customerIds);
		        }
		        if (in_array('paymentGateway', $searchCategory)){
			        $exp->add('c.id IN (:ids)');
			        $qb->setParameter('ids', $customerIds);
		        }
	        }

	        if($exp->count()) {
		        $qb->andWhere($exp);
	        }
        }

        return $qb;
    }

    private function getReferrerListQb(array $filters = []): QueryBuilder
    {
        $groupFilters = [];
        if (array_has($filters, 'filter')) {
            $groupFilters = array_get($filters, 'filter');
        }
        $queryBuilder = $this->createFilterQueryBuilder($groupFilters);

        if (isset($filters['search'])) {
            $exp = $queryBuilder->expr()->orX();
            $exp->addMultiple([
                "c.fullName LIKE :search",
                "(SELECT COUNT(crn.id) FROM " . MemberReferralName::class . " AS crn WHERE crn.member = c.id AND crn.name LIKE :search) > 0",
            ]);
            $queryBuilder->andWhere($exp)->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['excludeId'])) {
            $queryBuilder->andWhere('c.id !=' . $filters['excludeId']);
        }

        if (isset($filters['hasAffiateTag']) && $filters['hasAffiateTag'] === true) {
            $queryBuilder->andWhere("u.type = :type");
            $queryBuilder->setParameter('type', User::USER_TYPE_AFFILIATE);
        }

        return $queryBuilder;
    }

    /**
     * @deprecated since version 1.1
     *
     * @param mixed $filters
     *
     * @return array
     */
    public function getCustomerList($filters = null, $orders = [], int $limit = 20, int $offset = 0)
    {
        $status = true;
        $qb = $this->getCustomerListQb($filters);
        $qb
            ->select(
                "PARTIAL c.{id, fName, mName, country, lName, fullName, currency, balance, socials, level, isAffiliate, isCustomer, details, verifiedAt, joinedAt, tags, pinUserCode}, "
                . "PARTIAL u.{username, id, email, preferences, createdAt, isActive, activationTimestamp, creator}, "
                . "PARTIAL ccu.{id, name, code, rate}, "
                . "PARTIAL cr.{id, username}"
            )
            ->groupBy('c.id')
        ;

//        if (array_get($filters, 'withReferralCount', 1)) {
//            $qb->addSelect("(SELECT COUNT(ref.id) FROM " . \DbBundle\Entity\Customer::class . " ref WHERE ref.affiliate = c.id) referralCount");
//        }

        if (!empty($orders)) {
            foreach ($orders as $order) {
                if ( strpos($order['column'], ',') !== false ) {
                    foreach (explode(',', $order['column']) as $k => $v) {
                        $qb->addOrderBy($v, $order['dir']);
                    }
                } else {
                    $qb->addOrderBy($order['column'], $order['dir']);
                }
            }
        }

        if (isset($filters['length'])) {
            $qb->setMaxResults($filters['length']);
        } else {
            $qb->setMaxResults($limit);
        }
        if (isset($filters['start'])) {
            $qb->setFirstResult($filters['start']);
        } else {
            $qb->setFirstResult($offset);
        }

        if (isset($filters['excludeId'])) {
            $qb->andWhere('c.id !=' . $filters['excludeId']);
        }

        if (!empty($filters['excludeAffiliated'])) {
            $qb->andWhere('c.affiliate is NULL');
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function getReferrerList(?array $filters = null): array
    {
        $queryBuilder = $this->getReferrerListQb($filters);
        $queryBuilder
            ->select(
                "PARTIAL c.{id, fullName}, "
                . "PARTIAL u.{id, username}, "
                . "PARTIAL a.{id}"
            )
            ->leftJoin('c.affiliate', 'a');


        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function getAvailableReferrals($filters, $length, $start): array
    {
        $filters['excludeAffiliated'] = true;

        return $this->getCustomerList($filters, [], $length, $start);
    }

    public function getPossibleReferrers($excludeId, $filters, $length, $start): array
    {
        $filters['hasAffiateTag'] = true;
        if ($excludeId) {
            $filters['excludeId'] = $excludeId;
        }

        return $this->getReferrerList($filters, [], $length, $start);
    }

    public function getListQb($filters)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->join('c.user', 'u');
        $qb->join('c.currency', 'ccu');
        $qb->leftjoin('c.groups', 'ccg');

        if (array_has($filters, 'isAffiliate')) {
            $qb->andWhere('c.isAffiliate = :isAffiliate');
            $qb->setParameter('isAffiliate', array_get($filters, 'isAffiliate'));
        }

        if (array_has($filters, 'affiliate')) {
            $affiliate = array_get($filters, 'affiliate');
            if ($affiliate instanceof \DbBundle\Entity\Customer) {
                $affiliate = $affiliate->getId();
            } elseif (is_array($affiliate)) {
                $affiliate = array_get($affiliate, 'id');
            }
            $qb->andWhere('c.affiliate = :affiliate');
            $qb->setParameter('affiliate', $affiliate);
        }

        if (!empty($filters['search'])) {
            $exp = $qb->expr()->orX();
            $exp->add("c.fName LIKE :search");
            $exp->add("c.mName LIKE :search");
            $exp->add("c.lName LIKE :search");
            $exp->add("c.fullName LIKE :search");
            $exp->add("u.username LIKE :search");
            $exp->add("u.email LIKE :search");
            $exp->add("CONCAT(c.fName, ' ', c.lName)  LIKE :search");
            $exp->add('(SELECT COUNT(cps.id) FROM ' . CustomerProduct::class . ' AS cps WHERE cps.customer = c.id AND cps.userName LIKE  :search) > 0');

            $qb->andWhere($exp);
            $qb->setParameter('search', '%' . array_get($filters, 'search') . '%');
        }

        return $qb;
    }

    public function getList($filters = [], $orders = [], $selects = [], $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
    {
        $aliases = $this->getAliases();
        $qb = $this->getListQb($filters);
        $qb->select('c, u, ccu, ccg');

        if (isset($filters['length'])) {
            $qb->setMaxResults($filters['length']);
        }
        if (isset($filters['start'])) {
            $qb->setFirstResult($filters['start']);
        }
        $query = $qb->getQuery();
        $query->setFetchMode(\DbBundle\Entity\User::class, 'user', \Doctrine\ORM\Mapping\ClassMetadata::FETCH_EAGER);
        $query->setFetchMode(\DbBundle\Entity\Currency::class, 'currency', \Doctrine\ORM\Mapping\ClassMetadata::FETCH_EAGER);

        return $qb->getQuery()->getResult($hydrationMode);
    }

    public function getLatestCreatedCustomers($limit = 10, $offset = 0): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('c.id', 'c.fName', 'c.lName', 'c.fullName', 'c.joinedAt as createdAt');
        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);
        $qb->andWhere('c.isCustomer = :isCustomer');
        $qb->setParameter('isCustomer', true);
        $qb->orderBy('c.joinedAt', 'desc');

        return $qb->getQuery()->getResult();
    }

    public function getCustomerIds($filters = [], $orders = []): array
    {
        $aliases = $this->getAliases();
        $qb = $this->getListQb($filters);
        $qb->select('GROUP_CONCAT(DISTINCT c.id)');

        if (isset($filters['length'])) {
            $qb->setMaxResults($filters['length']);
        }
        if (isset($filters['start'])) {
            $qb->setFirstResult($filters['start']);
        }

        $query = $qb->getQuery();

        return explode(',', $qb->getQuery()->getResult(Query::HYDRATE_SINGLE_SCALAR));
    }

    public function getCustomerListFilterCount($filters = null)
    {
        $qb = $this->getCustomerListQb($filters);
        $qb->select('COUNT(c.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getTotalCustomer()
    {
        $queryBuilder = $this->createQueryBuilder('customer');
        $queryBuilder
            ->select('COUNT(customer.id)')
            ->andWhere('customer.isCustomer = :isCustomer')
            ->setParameter('isCustomer', true)
        ;

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getTotalAffiliate()
    {
        $queryBuilder = $this->createQueryBuilder('customer');
        $queryBuilder
            ->select('COUNT(customer.id)')
            ->andWhere('customer.isAffiliate = :isAffiliate')
            ->setParameter('isAffiliate', true)
        ;

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getCustomerListAllCount()
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('COUNT(c.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getAllPotentialReferralsOfMember(int $excludeMemberId): int
    {
        $qb = $this->createQueryBuilder('c');
        $qb->select('COUNT(c.id)');
        $qb->where($qb->expr()->neq('c.id', ':excludeMemberId'));
        $qb->setParameter('excludeMemberId', $excludeMemberId);

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getAvailableFilters()
    {
        return ['length', 'start', 'search', 'name'];
    }

    public function getAliases($reverse = false)
    {
        if ($reverse) {
            return [
                '_main_' => 'c',
                'customer.user' => ['p' => 'c', 'a' => 'u'],
                'customer.currency' => ['p' => 'c', 'a' => 'ccu'],
                'customer.country' => ['p' => 'c', 'a' => 'cco'],
            ];
        }

        return [
            'c' => ['main' => true, 'i' => 'id', 'must' => ['fName', 'lName', 'mName']],
            'u' => ['p' => 'c', 'c' => 'user', 'i' => 'id'],
            'ccu' => ['p' => 'c', 'c' => 'currency', 'i' => 'id', 'must' => ['code', 'name', 'rate']],
            'cco' => ['p' => 'c', 'c' => 'country', 'id' => 'id'],
        ];
    }

    public function getCustomer($filters = [], $orders = [], $limit = 5, $offset = 0, $select = [], $hydrationMode = Query::HYDRATE_OBJECT): array
    {
        $status = true;
        $qb = $this->createFilterQueryBuilder($filters);
        $qb->select(
            "PARTIAL c.{id, fName, mName, lName, fullName, country, currency, balance, socials, level, isAffiliate, isCustomer, details, verifiedAt, joinedAt, tags, pinUserCode}, "
            . "PARTIAL u.{username, id, email, preferences, createdAt, isActive,
                activationTimestamp, creator}, "
            . "PARTIAL ccu.{id, name, code, rate},"
            . "PARTIAL cr.{id, username}"

        )
            ->groupBy('c.id');

        $qb->setMaxResults($filters["limit"]);
        $qb->setFirstResult($filters["offset"]);

        $qb->addOrderBy('c.joinedAt', 'desc');

        return $qb->getQuery()->getResult($hydrationMode);
    }

    protected function createFilterQueryBuilder(array $filters = []): \Doctrine\ORM\QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder->join('c.user', 'u')
            ->leftjoin('c.currency', 'ccu')
            ->leftjoin('u.creator', 'cr')
        ;

        if (empty($filters)) {
            return $queryBuilder;
        }

        if (!empty(array_get($filters, 'product', []))) {
            $queryBuilder->andWhere('(SELECT COUNT(cp.id) FROM ' . CustomerProduct::class . ' AS cp WHERE c.id = cp.customerID AND cp.productID IN (:products)) > 0');
            $queryBuilder->setParameter('products', $filters['product']);
        }

        if (!empty(array_get($filters, 'currencies', []))) {
            $queryBuilder->andWhere('ccu.id IN (:currencies)')->setParameter('currencies', $filters['currencies']);
        }

        if (!empty(array_get($filters, 'country', []))) {
            $expression = $queryBuilder->expr()->orX(
                $queryBuilder->expr()->in('c.country', $filters['country'])
            );

            $hasUnknown = count(array_filter($filters['country'], function($country) {
                if ($country === "") {
                    return true;
                }
            })) > 0;

            if ($hasUnknown) {
                $expression->add('c.country IS NULL');
            }

            $queryBuilder->andWhere($expression);
        }

        if (!empty(array_get($filters, 'status', []))) {
            $exp = $queryBuilder->expr()->orX();
            foreach ($filters['status'] as $value) {
                if ($value == \DbBundle\Entity\Customer::CUSTOMER_REGISTERED) {
                    $exp->add("(u.isActive = true AND IFNULL(CAST(JSON_EXTRACT(c.details, '$.enabled') AS int), 0) = 0)");
                }
                if ($value == \DbBundle\Entity\Customer::CUSTOMER_ENABLED) {
                    $exp->add("(u.isActive = true AND IFNULL(CAST(JSON_EXTRACT(c.details, '$.enabled') AS int), 0) = 1)");
                }
                if ($value == \DbBundle\Entity\Customer::CUSTOMER_SUSPENDED) {
                    $exp->add("u.isActive = false");
                }
            }
            $queryBuilder->andWhere($exp);
        }

        if (!empty(array_get($filters, 'tag', []))) {
            $exp = $queryBuilder->expr()->orX();
            foreach ($filters['tag'] as $value) {
                if ($value === Member::ACRONYM_AFFILIATE) {
                    $exp->add("u.type = :type");
                    $queryBuilder->setParameter('type', User::USER_TYPE_AFFILIATE);
                }
                if ($value === Member::ACRONYM_MEMBER) {
                    $exp->add("u.type = :type");
                    $queryBuilder->setParameter('type', User::USER_TYPE_MEMBER);
                }
            }
            $queryBuilder->andWhere($exp);
        }

        if (!empty(array_get($filters, 'KYC'))) {
            $exp = $queryBuilder->expr()->orX();
            foreach ($filters['KYC'] as $value) {
                if ($value) {
                    $exp->add('c.verifiedAt IS NOT NULL ');
                } else {
                    $exp->add('c.verifiedAt IS NULL');
                }
            }
            $queryBuilder->andWhere($exp);
        }

        if (!empty(array_get($filters, 'from', ''))) {
            $queryBuilder->andWhere('c.joinedAt >= :from');
            $queryBuilder->setParameter('from', new DateTime($filters['from']));
        }

        if (!empty(array_get($filters, 'to', ''))) {
            $queryBuilder->andWhere('c.joinedAt < :to');
            $queryBuilder->setParameter('to', (new DateTime($filters['to'] . '+1 day')));
        }

        return $queryBuilder;
    }

    public function getMemberWithReferral(int $memberId): ?\DbBundle\Entity\Customer
    {
        $queryBuilder = $this->createQueryBuilder('c');
        $queryBuilder
            ->leftJoin('c.referrals', 'r')
            ->select('c, r')
            ->where('c.id = :memberId')
            ->setParameter('memberId', $memberId)
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }


    public function findByPinUserCode($userCode)
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('c')
            ->where($qb->expr()->eq('c.pinUserCode', ':pin_user_code'))
//            ->andWhere($qb->expr()->eq('u.type', ':type'))
            ->setParameter('pin_user_code', $userCode)
//            ->setParameter('type', $type)
        ;

//        if ($isActivated) {
//            $qb->andWhere($qb->expr()->isNotNull('u.activationTimestamp'));
//        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function hasSomeMemberUsingRiskSetting(string $resourceId): bool
    {
        $queryBuilder = $this->createQueryBuilder('c');

        $queryBuilder
            ->select('count(c.id)')
            ->where('c.riskSetting = :resourceId')
            ->setParameter('resourceId', $resourceId)
        ;

        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        return $count > 0;
    }

    public function getAllReferralProductListByReferrer(array $filters, array $orders, int $referrerId, int $offset, int $limit): array
    {
        $queryBuilder = $this->getReferralProductListByReferrerQb($filters, $orders, $referrerId);

        $queryBuilder
            ->select(
                'm.id AS memberId', 'm.pinUserCode AS pinUserCode', 'mp.id AS memberProductId', 'p.id AS productId',
                'p.name AS productName', 'c.code AS currencyCode'
            );

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function getReferralProductListByReferrer(array $filters, array $orders, int $referrerId, int $offset, int $limit): array
    {
        $queryBuilder = $this->getReferralProductListByReferrerQb($filters, $orders, $referrerId);

        $queryBuilder
            ->select(
                'm.id AS memberId', 'm.pinUserCode AS pinUserCode', 'mp.id AS memberProductId', 'p.id AS productId',
                'p.name AS productName', 'c.code AS currencyCode'
            )
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    public function getReferralProductListFilterCountByReferrer(array $filters): int
    {
        $queryBuilder = $this->getReferralProductListByReferrerQb($filters, []);

        $queryBuilder->select('COUNT(m.id)');

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getReferralProductListTotalCountByReferrer(array $filters, int $affiliateUserId): int
    {
        $queryBuilder = $this->createQueryBuilder('m');

        $queryBuilder
            ->select('COUNT(m.id)')
            ->where('m.affiliate = :affiliateUserId')
            ->setParameter('affiliateUserId', $affiliateUserId);
            // ->join('m.affiliate', 'a', Join::WITH, $queryBuilder->expr()->eq('a.id', ':referrerId'))
            // ->setParameter('referrerId', $referrerId);

        if (array_has($filters, 'orderBy')) {
            if (array_get($filters, 'orderBy') == 'productName') {
                $queryBuilder->join('m.products', 'mp')
                    ->join('mp.product', 'p', Join::WITH, "JSON_EXTRACT(p.details, '$.ac_wallet') IS NULL");
            } elseif (array_get($filters, 'orderBy') == 'memberId') {
                $queryBuilder->leftJoin('m.products', 'mp',
                    Join::WITH, $queryBuilder->expr()->notIn('mp.product', ':piwiWalletProductId'))
                    ->leftJoin('mp.product', 'p')
                    ->setParameter('piwiWalletProductId', $filters['piwiWalletProductId']);
            }
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    private function getReferralProductListByReferrerQb(array $filters, array $orders): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('m');

        $queryBuilder
            ->join('m.currency', 'c')      
            ->where('m.affiliate = :affiliateUserId')
            ->setParameters(['affiliateUserId' => $filters['affiliateUserId']])      
        ;

        if (array_has($filters, 'orderBy')) {
            if (array_get($filters, 'orderBy') == 'productName') {
                $queryBuilder->join('m.products', 'mp')
                    ->join('mp.product', 'p', Join::WITH, "JSON_EXTRACT(p.details, '$.piwi_wallet') IS NULL");
            } elseif (array_get($filters, 'orderBy') == 'memberId') {
                $queryBuilder->leftJoin('m.products', 'mp',
                    Join::WITH, $queryBuilder->expr()->notIn('mp.product', ':piwiWalletProductId'))
                    ->leftJoin('mp.product', 'p')
                    ->setParameter('piwiWalletProductId', $filters['piwiWalletProductId']);
            }
        }

        if (!empty($orders)) {
            foreach ($orders as $order) {
                $queryBuilder->addOrderBy($order['column'], $order['dir']);
            }
        }

        if (array_has($filters, 'search') && array_get($filters, 'search')) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('p.name', ':productSearch'),
                    $queryBuilder->expr()->like('m.id', ':productSearch')
                )
            )
            ->setParameter('productSearch', array_get($filters, 'search'));
        }

        if (array_has($filters, 'memberProductIds') && array_get($filters, 'memberProductIds')) {
            $queryBuilder->andWhere($queryBuilder->expr()->in('mp.id', ':memberProductIds'))
                ->setParameter('memberProductIds', array_get($filters, 'memberProductIds'));
        }

        
        return $queryBuilder;
    }

    public function getByUserId($userId)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->andWhere('c.user = :user');
        $qb->setParameter('user', $userId);

        return $qb->getQuery()->getSingleResult();
    }
}
