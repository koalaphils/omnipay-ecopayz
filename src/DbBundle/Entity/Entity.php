<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace DbBundle\Entity;

/**
 * Description of Entity.
 *
 * @author cnonog
 */
class Entity
{
    /**
     * @var int
     */
    protected $id;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    
    public function setId($id): void
    {
        $this->id = $id;
    }

    public function hasBeenPersisted(): bool
    {
        return $this->id !== null;
    }
}
