<?php

namespace DbBundle\Entity\Interfaces;

/**
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
interface TimestampInterface
{
    /**
     * Return the date of creation.
     *
     * @return \DateTime|null The date when it was updated
     */
    public function getCreatedAt();

    /**
     * Return the date when it was updated.
     *
     * @return \DateTime|null The date when it was updated
     */
    public function getUpdatedAt();

    /**
     * Set the date of the creation.
     *
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt);

    /**
     * Set the date when it was updated.
     *
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt);
}
