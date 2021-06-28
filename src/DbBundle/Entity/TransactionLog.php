<?php

namespace DbBundle\Entity;

//use DbBundle\Entity\Interfaces\ActionInterface;
//use DbBundle\Entity\Interfaces\AuditAssociationInterface;
//use DbBundle\Entity\Interfaces\AuditInterface;
//use DbBundle\Entity\Interfaces\TimestampInterface;

/**
 * TransactionLog
 */
class TransactionLog extends Entity {

    private $transaction_id;
    private $old_status;
    private $new_status;
    private $is_voided;
    private $created_by;
    private $created_at;
    
    public function getTransactionId()
    {
        return $this->transaction_id;
    }
    
    public function setTransactionId($transaction_id)
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }
    
    public function getOldStatus()
    {
        return $this->old_status;
    }
    
    public function setOldStatus($old_status)
    {
        $this->old_status = $old_status;
        return $this;
    }
    
    public function getNewStatus()
    {
        return $this->new_status;
    }
    
    public function setNewStatus($new_status)
    {
        $this->new_status = $new_status;
        return $this;
    }
    
    public function getCreatedBy()
    {
        return $this->created_by;
    }
    
    public function setCreatedBy($created_by)
    {
        $this->created_by = $created_by;
        return $this;
    }
    
    public function getIsVoided()
    {
        return $this->is_voided;
    }
    
    public function setIsVoided($is_voided)
    {
        $this->is_voided = $is_voided;
        return $this;
    }
    
    public function getCreatedAt()
    {
        return $this->created_at;
    }
    
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }
}
