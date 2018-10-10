<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;

/**
 * UserGroup.
 */
class UserGroup extends Entity implements ActionInterface, TimestampInterface, AuditInterface, AuditAssociationInterface
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;
    /**
     * @var string
     */
    private $name;

    /**
     * @var json
     */
    private $roles;

    public function __construct()
    {
        $this->roles = [];
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return UserGroup
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set roles.
     *
     * @param json $roles
     *
     * @return UserGroup
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Get roles.
     *
     * @return json
     */
    public function getRoles()
    {
        return $this->roles;
    }

    public function getFlattenRoles(): array
    {
        $flattenRoles = [];
        foreach ($this->roles as $role) {
            $flattenRoles[] = $role['value'];
        }

        return $flattenRoles;
    }

    public function getCategory()
    {
        return AuditRevisionLog::CATEGORY_USER_GROUP;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt'];
    }

    public function getAssociationFields()
    {
        return [];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getLabel()
    {
        return $this->getName();
    }

    public function isAudit()
    {
        return true;
    }

    public function getAssociationFieldName()
    {
        return $this->getName();
    }

    public function getAuditDetails(): array
    {
        return ['name'];
    }
}
