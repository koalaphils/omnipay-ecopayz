<?php

namespace DbBundle\Repository;

/**
 * CustomerPaymentOptionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CustomerPaymentOptionRepository extends \Doctrine\ORM\EntityRepository
{        
    /*zimi**/
    // public function getListByCustomerId($customer_id): array
    // {        
    //     $qb = $this->createQueryBuilder('c');
    //     $qb->select('c.customer_payment_option_id' as 'id', 'c.customer_payment_options_customer_id' as 'cid', 'c.customer_payment_option_type' as 'type');        
    //     $qb->andWhere('c.customer_payment_options_customer_id = :cid');
    //     $qb->andWhere('c.customer_payment_option_is_active = :is_active');        
    //     $qb->setParameter('cid', $customer_id);
    //     $qb->setParameter('is_active', 1);
    //     $qb->orderBy('c.customer_payment_option_created_at', 'desc');

    //     return $qb->getQuery()->getResult();
    // }

    public function findByidAndCustomer($id, $customerId, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT)
    {
        $qb = $this->createQueryBuilder('cp');
        $qb->where('cp.id = :id')->setParameter('id', $id);
        $qb->andWhere('cp.customer = :customerId')->setParameter('customerId', $customerId);

        return $qb->getQuery()->getOneOrNullResult($hydrationMode);
    }

    public function findByCustomerPaymentOptionAndEmail($customerId, $paymentOption, $email)
    {
        $qb = $this->createQueryBuilder('cpo');

        $qb->join('cpo.customer', 'c', 'WITH', 'c.id = :customerId')
            ->join('cpo.paymentOption', 'po', 'WITH', 'po.code = :paymentOptionCode')
            ->where("JSON_CONTAINS(cpo.fields, JSON_OBJECT('email', :email)) = 1")
            ->setParameter('customerId', $customerId)
            ->setParameter('paymentOptionCode', $paymentOption)
            ->setParameter('email', $email);

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByMemberPaymentOptionAccountId(int $memberId, string $accountId)
    {
        $qb = $this->createQueryBuilder('cpo');

        $qb->join('cpo.customer', 'c', 'WITH', 'c.id = :customerId')
            ->join('cpo.paymentOption', 'po', 'WITH', 'po.code = :paymentOptionCode')
            ->where("JSON_EXTRACT(cpo.fields, '$.account_id') = :account_id")
            ->setParameter('customerId', $memberId)
            ->setParameter('paymentOptionCode', 'BITCOIN')
            ->setParameter('account_id', $accountId);

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function disableOldPaymentOption($customerPaymentOptionId, $customerId, $paymentOptionCode)
    {
        $qb = $this->createQueryBuilder('cpo');
        $qb->update('DbBundle:CustomerPaymentOption', 'cpo')
            ->set('cpo.isActive', ':deactivate')
            ->where('cpo.customer = :customerId')
            ->setParameter('deactivate', false)
            ->setParameter('customerId', $customerId)
            ->andWhere('cpo.paymentOption = :paymentOptionCode')
            ->setParameter('paymentOptionCode', $paymentOptionCode)
            ->andWhere($qb->expr()->neq('cpo.id', ':customerPaymentOptionId'))
            ->setParameter('customerPaymentOptionId', $customerPaymentOptionId)
        ;

        $qb->getQuery()->execute();
    }

    public function findMemberPaymentOptionDuplicateInCodeAndValue(?int $memberPaymentOptionId, string $paymentOption, string $code, string $codeValue)
    {
        $queryBuilder = $this->createQueryBuilder('cpo');

        $queryBuilder
            ->where("JSON_CONTAINS(cpo.fields, JSON_OBJECT(:code, :value)) = 1")
            ->andWhere($queryBuilder->expr()->eq('cpo.paymentOption', ':paymentOptionCode'))
            ->setParameter('paymentOptionCode', $paymentOption)
            ->setParameter('code', $code)
            ->setParameter('value', $codeValue)
        ;
        
        if (!is_null($memberPaymentOptionId)) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->neq('cpo.id', ':memberPaymentOptionId'))
                ->setParameter('memberPaymentOptionId', $memberPaymentOptionId)
            ;
        }
        $queryBuilder->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }
}
