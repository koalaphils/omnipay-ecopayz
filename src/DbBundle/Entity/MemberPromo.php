<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\Promo;

class MemberPromo extends Entity implements ActionInterface, TimestampInterface, AuditInterface, AuditAssociationInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    private $promo;
    private $referrer;
    private $member;
    private $transaction;

    public function __construct()
    {}
    
    public function setPromo($promo)
    {
        $this->promo = $promo;

        return $this;
    }

    public function getPromo(): Promo
    {
        return $this->promo;
    }

    public function setReferrer($referrer)
    {
        $this->referrer = $referrer;

        return $this;
    }

    public function getReferrer(): Member
    {
        return $this->referrer;
    }
    
    public function setMember(Member $member)
    {
        $this->member = $member;

        return $this;
    }

    public function getMember(): Member
    {
        return $this->member;
    }

    public function setTransaction(?Transaction $transaction)
    {
        $this->transaction = $transaction;

        return $this;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_MEMBER_PROMO;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt'];
    }

    public function getAssociationFields()
    {
        return ['promo', 'member', 'referrer', 'transaction'];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getLabel()
    {
        return $this->getPromo()->getName();
    }

    public function isAudit()
    {
        return true;
    }

    public function getAssociationFieldName()
    {
        return $this->getLabel();
    }

    public function getAuditDetails(): array
    {
        return ['member' => $this->getMember(), 'referrer' => $this->getReferrer(), 'promo' => $this->getPromo(), 'transaction' => $this->getTransaction()];
    }
}