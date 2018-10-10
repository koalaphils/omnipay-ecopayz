<?php

namespace DbBundle\Entity\Interfaces;

interface VersionInterface
{
    public function getVersionColumn();

    public function getVersionType();
}
