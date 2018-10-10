<?php

namespace DbBundle\Listener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use DbBundle\Entity\Interfaces\GatewayInterface;
use DbBundle\Entity\Gateway;

class GatewayListener
{
    private $gatewayLogManager;

    private $arrayHelper;

    private $gatewayLogs;

    private $uow;

    private $em;

    public function onFlush(OnFlushEventArgs $args)
    {
        $this->em = $args->getEntityManager();
        $this->uow = $this->em->getUnitOfWork();

        $entities = array_merge(
            $this->uow->getScheduledEntityInsertions(),
            $this->uow->getScheduledEntityUpdates()
        );

        foreach ($entities as $key => $entity) {
            if ($entity instanceof GatewayInterface && $entity->processGateway()) {
                $gateway = $entity->getAccount();
                $this->processGatewayAmount(
                    $key,
                    $entity,
                    $gateway,
                    $entity->getFinalAmount(),
                    $entity->getOperation()
                );

                $this->em->persist($gateway);
                $gatewayMetadata = $this->em->getClassMetadata(get_class($gateway));
                $this->uow->recomputeSingleEntityChangeSet($gatewayMetadata, $gateway);

                $gatewayTo = $entity->getAccountTo();
                if ($gatewayTo instanceof Gateway) {
                    $this->processGatewayAmount(
                        $key . '-to',
                        $entity,
                        $gatewayTo,
                        $entity->getFinalAmount(true),
                        $entity->getOperation(true)
                    );
                    $this->em->persist($gatewayTo);
                    $gatewayToMetadata = $this->em->getClassMetadata(get_class($gatewayTo));
                    $this->uow->recomputeSingleEntityChangeSet($gatewayToMetadata, $gatewayTo);
                }
            }
        }
    }

    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $this->setEntityIdentifierByHash($eventArgs);
    }

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $this->setEntityIdentifierByHash($eventArgs);
    }

    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        $gatewayLogs = $this->gatewayLogs;
        $this->gatewayLogs = [];

        if (!empty($gatewayLogs)) {
            foreach ($gatewayLogs as $gatewayLog) {
                $classMetadata = $this->em->getClassMetadata(get_class($gatewayLog));
                $this->em->persist($gatewayLog);
                $this->uow->computeChangeSet($classMetadata, $gatewayLog);
            }

            $this->em->flush();
        }
    }

    private function setEntityIdentifierByHash(LifecycleEventArgs $eventArgs)
    {
        if ($eventArgs->getObject() instanceof GatewayInterface) {
            $object = $eventArgs->getObject();
            $hash = spl_object_hash($object);

            if ($object->getAccountTo()) {
                $hash .= '-to';
            }

            $gatewayLog = $this->getArrayHelper()->get($this->gatewayLogs, $hash, null);

            if (!is_null($gatewayLog)) {
                $gatewayLog->setDetails(
                    array_merge(
                        [
                            'identifier' => $object->getIdentifier(),
                            'reference_class' => get_class($object),
                        ],
                        $gatewayLog->getDetails()
                    )
                );
                $this->gatewayLogs[$hash] = $gatewayLog;
            }
        }
    }

    private function processGatewayAmount($key, GatewayInterface $entity, Gateway $gateway, $amount, $operation)
    {
        $this->audit($key, $entity, $gateway, $operation, $amount);

        if ($operation == GatewayInterface::OPERATION_ADD) {
            $gateway->add($amount);
        } elseif ($operation == GatewayInterface::OPERATION_SUB) {
            $gateway->sub($amount);
        }
    }

    private function audit($key, GatewayInterface $entity, Gateway $gateway, $operation, $amount)
    {
        $this->gatewayLogs[$key] = $this->getGatewayLogManager()->audit($entity, $gateway, $operation, $amount);
    }

    public function setGatewayLogManager(\GatewayTransactionBundle\Manager\GatewayLogManager $manager)
    {
        return $this->gatewayLogManager = $manager;
    }

    private function getGatewayLogManager()
    {
        return $this->gatewayLogManager;
    }

    public function setArrayHelper(\AppBundle\Helper\ArrayHelper $helper)
    {
        $this->arrayHelper = $helper;
    }

    private function getArrayHelper()
    {
        return $this->arrayHelper;
    }
}
