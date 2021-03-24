<?php

namespace DbBundle\Entity\Interfaces;

/**
 * Action Interface.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
interface ActionInterface
{
    /**
     * Get the user who created the record.
     *
     * @return \Symfony\Component\Security\Core\User\UserInterface
     */
    public function getCreatedBy();

    /**
     * Get the user who updated the record.
     *
     * @return null|\Symfony\Component\Security\Core\User\UserInterface
     */
    public function getUpdatedBy();

    /**
     * Set the creator of the record.
     *
     * @param type $createdBy
     */
    public function setCreatedBy($createdBy);

    /**
     * Set the who updated the record.
     *
     * @param null|\Symfony\Component\Security\Core\User\UserInterface $updatedBy
     */
    public function setUpdatedBy($updatedBy);
}
