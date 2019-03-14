<?php

namespace DbBundle\Entity;

//use DbBundle\Entity\Interfaces\ActionInterface;
//use DbBundle\Entity\Interfaces\AuditAssociationInterface;
//use DbBundle\Entity\Interfaces\AuditInterface;
//use DbBundle\Entity\Interfaces\TimestampInterface;

/**
 * TransactionLog
 */
class Notification extends Entity {

    private $user_id;
    private $message;
    private $style;
    private $created_at;
    
    public function getUserID()
    {
        return $this->user_id;
    }
    
    public function setUserID($user_id)
    {
        $this->user_id = $user_id;
        return $this;
    }
    
    public function getMessage()
    {
        return $this->$message;
    }
    
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }
    
    public function getStyle()
    {
        return $this->style;
    }
    
    public function setStyle($style)
    {
        $this->style = $style;
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
