<?php

namespace DbBundle\Listener;

use AppBundle\Helper\ArrayHelper;
use AuditBundle\Manager\AuditManager;
use DbBundle\Entity\Entity;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\AuditAssociationInterface;
use DbBundle\Entity\AuditRevisionLog;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AuditListener
{
    private $auditManager;

    private $arrayHelper;

    private $auditRevisionLogs = [];

    private $eventDispatcher;

    /**
     *
     * @var \Doctrine\ORM\UnitOfWork
     */
    private $uow;

    /**
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $this->em = $eventArgs->getEntityManager();
        $this->uow = $this->em->getUnitOfWork();

        foreach ($this->uow->getScheduledEntityInsertions() as $key => $entity) {
            if ($entity instanceof AuditInterface && $entity->isAudit()) {
                $changedPropertiesData = $this->getChangedPropertiesData($entity);
                $auditDetails = $this->getAuditDetails($entity);

                if (!empty($changedPropertiesData)) {
                    $this->audit($key, $entity, AuditRevisionLog::OPERATION_CREATE, $changedPropertiesData, null, $auditDetails);
                }
            }
        }

        foreach ($this->uow->getScheduledEntityUpdates() as $key => $entity) {
            if ($entity instanceof AuditInterface && $entity->isAudit()) {
                $changedPropertiesData = $this->getChangedPropertiesData($entity);
                $auditDetails = $this->getAuditDetails($entity);

                if (!empty($changedPropertiesData)) {
                    $this->audit($key, $entity, AuditRevisionLog::OPERATION_UPDATE, $changedPropertiesData, null, $auditDetails);
                }
            }
        }
        foreach ($this->uow->getScheduledEntityDeletions() as $key => $entity) {
            if ($entity instanceof AuditInterface && $entity->isAudit()) {
                $auditDetails = $this->getAuditDetails($entity);

                $this->audit($key, $entity, AuditRevisionLog::OPERATION_DELETE);
                $this->audit($key, $entity, AuditRevisionLog::OPERATION_DELETE, [], null, $auditDetails);
            }
        }
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        if ($eventArgs->getObject() instanceof AuditInterface) {
            $object = $eventArgs->getObject();
            $hash = spl_object_hash($object);
            $auditRevisionLog = $this->arrayHelper->get($this->auditRevisionLogs, $hash, null);

            if (!is_null($auditRevisionLog)) {
                $auditRevisionLog->setDetails(array_merge(
                    $auditRevisionLog->getDetails(),
                    ['identifier' => $object->getIdentifier()]
                ));
                $this->auditRevisionLogs[$hash] = $auditRevisionLog;
            }
        }
    }

    public function preRemove(LifecycleEventArgs $eventArgs)
    {
        if ($eventArgs->getObject() instanceof AuditInterface) {
            $object = $eventArgs->getObject();
            $hash = spl_object_hash($object);
            $auditRevisionLog = $this->arrayHelper->get($this->auditRevisionLogs, $hash, null);

            if (!is_null($auditRevisionLog)) {
                $auditRevisionLog->setDetails(array_merge(
                    $auditRevisionLog->getDetails(),
                    ['identifier' => $object->getIdentifier()]
                ));
                $this->auditRevisionLogs[$hash] = $auditRevisionLog;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        $auditRevisionLogs = $this->auditRevisionLogs;
        $this->auditRevisionLogs = [];

        if (!empty($auditRevisionLogs)) {
            $auditRevision = $this->auditManager->createRevision();
            $this->em->persist($auditRevision);

            foreach ($auditRevisionLogs as $auditRevLog) {
                $auditRevLog->setAuditRevision($auditRevision);
                $classMetadata = $this->em->getClassMetadata(get_class($auditRevLog));
                $this->em->persist($auditRevLog);
                $this->uow->computeChangeSet($classMetadata, $auditRevLog);
            }

            $this->em->flush();
        }
    }

    private function getChangedPropertiesData(AuditInterface $entity)
    {
        $entityChangeSet = $this->uow->getEntityChangeSet($entity);
        $ignoreFields = $entity->getIgnoreFields();
        $associationFields = $entity->getAssociationFields();
        $changedPropertiesData = array_diff_key($entityChangeSet, array_flip($ignoreFields));

        if (!empty($associationFields)) {
            foreach ($associationFields as $associationField) {
                if (isset($changedPropertiesData[$associationField])) {
                    $oldValue = $changedPropertiesData[$associationField][0];
                    $newValue = $changedPropertiesData[$associationField][1];
                    if ($newValue instanceof Entity && !is_null($newValue->getId())){
                        $this->em->refresh($newValue);
                    }

                    if ($oldValue instanceof AuditAssociationInterface) {
                        $changedPropertiesData[$associationField][0] = $oldValue->getAssociationFieldName();
                    }

                    if ($newValue instanceof AuditAssociationInterface) {
                        $changedPropertiesData[$associationField][1] = $newValue->getAssociationFieldName();
                    }
                }
            }
        }

        foreach ($changedPropertiesData as $key => $changedPropertiesDatum) {
            if (is_array($changedPropertiesDatum)) {
                if ($changedPropertiesDatum[0] == $changedPropertiesDatum[1]) {
                    unset($changedPropertiesData[$key]);
                }
            }
        }

        return $changedPropertiesData;
    }

    private function getAuditDetails(AuditInterface $entity, array $treeHashes = [])
    {
        $entityDetails = ['id' => $entity->getId()];
        $treeHashes[] = spl_object_hash($entity);

        foreach ($entity->getAuditDetails() as $field => $value) {
            if ($value instanceof AuditInterface && $value) {
                if (!in_array(spl_object_hash($value), $treeHashes)) {
                    $this->uow->initializeObject($value);
                    $entityDetails[$field] = $this->getAuditDetails($value, $treeHashes);
                }
            } elseif ($value instanceof \DateTime) {
                $entityDetails[$field] = $value->format('Y-m-d H:i:s');
            } else {
                $entityDetails[$field] = $value;
            }
        }

        return $entityDetails;
    }

    private function audit($key, AuditInterface $entity, $operation, $changedProperties = null, $category = null, array $auditDetails = [])
    {
        $this->auditRevisionLogs[$key] = $this->auditManager->createRevisionLog(
            $entity,
            $operation,
            $category,
            $changedProperties,
            $auditDetails
        );
    }

    public function setAuditManager(AuditManager $auditManager)
    {
        $this->auditManager = $auditManager;
    }

    public function setArrayHelper(ArrayHelper $arrayHelper)
    {
        $this->arrayHelper = $arrayHelper;
    }
}
