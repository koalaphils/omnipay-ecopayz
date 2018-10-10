<?php

namespace DbBundle\Entity\Interfaces;

interface AuditInterface
{
    public function getCategory();

    public function getIgnoreFields();

    public function getAssociationFields();

    public function getIdentifier();

    public function getLabel();

    public function getAuditDetails(): array;

    public function isAudit();
}