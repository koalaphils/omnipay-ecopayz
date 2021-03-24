<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DbBundle\Entity\Traits;

/**
 * Description of SoftDelete.
 *
 * @author cnonog
 */
trait SoftDeleteTrait
{
    /**
     * @var \DateTime
     */
    protected $deletedAt;

    /**
     * Set userDeletedAt.
     *
     * @param \DateTime $deletedAt
     *
     * @return User
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get userDeletedAt.
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Check if the entity is deleted.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->getDeletedAt() !== null;
    }
}
