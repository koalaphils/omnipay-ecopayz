<?php

namespace DbBundle\Repository;

use DbBundle\Entity\Notification;
use DbBundle\Repository\CustomerRepository;

//use DbBundle\Entity\PaymentOption;
//use Doctrine\ORM\Query;
//use DbBundle\Entity\Transaction;
//use DbBundle\Entity\SubTransaction;
//use DbBundle\Entity\User;
//use DbBundle\Entity\Currency;
//use DbBundle\Entity\CustomerProduct;
//use DbBundle\Entity\Product;
//use Doctrine\ORM\QueryBuilder;

/**
 * TransactionLogRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class NotificationRepository extends BaseRepository {

    public function save($entity) {
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

    public function getStyleText($status = 0) {
        $style = "orange";
        switch ($status) {
            case 2:
                $style = "green";
                break;
            case 3:
                $style = "red";
                break;
            case 4:
                $style = "blue";
                break;
            default:
                break;
        }
        return $style;
    }

}
