<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Traits\ActionTrait;
use DbBundle\Entity\Traits\TimestampTrait;
use Doctrine\Common\Collections\ArrayCollection;

class MemberTag extends Entity implements ActionInterface, TimestampInterface, AuditAssociationInterface
{
    use ActionTrait;
    use TimestampTrait;

    private $name;
    private $description;
    private $isDeleteEnabled;
    private $members;

    public function __construct(string $name, string $description, bool $isDeleteEnabled = false)
    {
        $this->name = $name;
        $this->isDeleteEnabled = $isDeleteEnabled;
        $this->description = $description;
        $this->members = new ArrayCollection();
    }

    public function getName(){
        return $this->name;
    }
    public function getDescription(){
        return $this->description;
    }
    public function isDeleteEnabled(){
        return $this->isDeleteEnabled;
    }
    public function setName(?string $name){
        $this->name = $name;
        return $this;
    }
    public function setDescription(?string $description){
        $this->description = $description;
        return $this;
    }
    public function setIsDeleteEnabled(bool $isDeleteEnabled){
        $this->isDeleteEnabled = $isDeleteEnabled;
        return $this;
    }
    public function getMembers(){
        return $this->members ?? new ArrayCollection();
    }
    public function setMembers($members){
        $this->members = $members;
        return $this;
    }
    public function getIdentifier(){
        return $this->id;
    }

    public function getAssociationFieldName()
    {
        return $this->getName();
    }

    public function addMember(Customer $customer): MemberTag
    {
        $this->members = $this->members ?? new ArrayCollection();
        if ($this->members->contains($customer)) {
            return $this;
        }

        $this->members->add($customer);
        $customer->addMemberTags($this);

        return $this;
    }

    public function removeMember(Customer $customer): MemberTag
    {
        $this->members = $this->members ?? new ArrayCollection();
        if (!$this->members->contains($customer)) {
            return $this;
        }

        $this->members->removeElement($customer);
        $customer->removeMemberTags($this);

        return $this;
    }
}
