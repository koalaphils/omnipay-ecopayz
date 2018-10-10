<?php

namespace DbBundle\Entity\Traits;

/**
 * @author Cydrick Nonog <cydrick.dev@gmail.com>
 */
trait TimestampTrait
{
    protected $createdAt;
    protected $updatedAt;

    /**
     * Return the date of creation.
     *
     * @return \DateTime|null The date when it was updated
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Return the date when it was updated.
     *
     * @return \DateTime|null The date when it was updated
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set the date of the creation.
     *
     * @param \DateTime $createdAt
     *
     * @return self
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Set the date when it was updated.
     *
     * @param \DateTime $updatedAt
     *
     * @return self
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
