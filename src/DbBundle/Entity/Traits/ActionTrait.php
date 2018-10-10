<?php

namespace DbBundle\Entity\Traits;

trait ActionTrait
{
    /**
     * @var int
     */
    protected $createdBy;

    /**
     * @var int
     */
    protected $updatedBy;

    /**
     * Set createdBy.
     *
     * @param int $createdBy
     *
     * @return User
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy.
     *
     * @return int
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set updatedBy.
     *
     * @param int $updatedBy
     *
     * @return User
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * Get updatedBy.
     *
     * @return int
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }
}
