<?php

namespace GatewayTransactionBundle\Manager;

use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\Gateway;
use DbBundle\Entity\GatewayLog;
use DbBundle\Entity\GatewayTransaction;
use DbBundle\Entity\Interfaces\GatewayInterface;
use DbBundle\Entity\Transaction;

class GatewayLogManager extends AbstractManager
{
    public function getList($filters = null)
    {
        $results = [];

        if (array_get($filters, 'datatable', 0)) {
            if (false !== array_get($filters, 'search.value', false)) {
                $filters['search'] = $filters['search']['value'];
            }
            $orders = (!array_has($filters, 'order')) ? [['column' => 'gl.timestamp', 'dir' => 'desc'], ['column' => 'gl.id', 'dir' => 'desc']] : $filters['order'];

            $results['data'] = $this->getRepository()->getList($filters, $orders, \Doctrine\ORM\Query::HYDRATE_OBJECT);
            $results['draw'] = $filters['draw'];
            $results['recordsFiltered'] = $this->getRepository()->getListFilterCount($filters);
            $results['recordsTotal'] = $this->getRepository()->getListAllCount();
        } else {
            $results = $this->getRepository()->getList($filters);
        }

        return $results;
    }

    public function audit(GatewayInterface $entity, Gateway $gateway, $operation, $amount)
    {
        $gatewayLog = $this->createLog(
            $operation,
            $amount,
            $gateway->getBalance(),
            $entity->getReferenceNumber(),
            $entity->getGatewayCurrency(),
            $gateway,
            $entity->getGatewayPaymentOption(),
            $entity->getTransactionDetails()
        );

        return $gatewayLog;
    }

    public function createLog($operation, $amount, $balance, $referenceNumber, $currency, $gateway, $paymentOption, $details = [])
    {
        $gatewayLog = new GatewayLog();
        $gatewayLog->setType(GatewayLog::translateOperationToType($operation));
        $gatewayLog->setAmount($amount);
        $gatewayLog->setBalance($balance);
        $gatewayLog->setReferenceNumber($referenceNumber);
        $gatewayLog->setCurrency($currency);
        $gatewayLog->setGateway($gateway);
        $gatewayLog->setPaymentOption($paymentOption);
        $gatewayLog->setDetails($details);

        return $gatewayLog;
    }

    public function findLastGatewayLogByIdentifierAndClass(string $class, string $identifier): ?GatewayLog
    {
        return $this->getRepository()->findLastGatewayLogByIdentifierAndClass($class, $identifier);
    }

    public function redirect($id)
    {
        $gatewayLog = $this->getRepository()->find($id);

        return $this->getRouteFromClass($gatewayLog);
    }

    private function getRouteFromClass(GatewayLog $gatewayLog)
    {
        $referenceClass = $gatewayLog->getDetail('reference_class');
        $identifier = $gatewayLog->getDetail('identifier');
        $referenceNumber = $gatewayLog->getReferenceNumber();
        $options = [];

        if ($referenceClass == GatewayTransaction::class) {
            $entity = $this->getEntityFromClass($referenceClass, $identifier, $referenceNumber);

            $options = [
                'route' => 'gateway_transaction.update_page',
                'params' => [
                    'type' => GatewayTransaction::translateType($entity->getType(), true),
                    'id' => $entity->getId(),
                ],
            ];
        } elseif ($referenceClass == Transaction::class) {
            $entity = $this->getEntityFromClass($referenceClass, $identifier, $referenceNumber);

            $options = [
                'route' => 'transaction.update_page',
                'params' => [
                    'type' => $entity->getTypeText(),
                    'id' => $entity->getId(),
                ],
            ];
        } elseif ($referenceClass == Gateway::class) {
            $entity = $this->getEntityFromClass($referenceClass, $identifier, $referenceNumber);

            $options = [
                'route' => 'gateway.update_page',
                'params' => [
                    'id' => $entity->getId(),
                ],
            ];
        }

        return $options;
    }

    private function getEntityFromClass($referenceClass, $identifier, $referenceNumber)
    {
        $repository = $this->getDoctrine()->getRepository($referenceClass);

        if ($identifier) {
            $entity = $repository->find($identifier);
        } elseif ($referenceNumber) {
            $entity = $repository->findByReferenceNumber($referenceNumber);
        }

        return $entity;
    }

    protected function getRepository(): \DbBundle\Repository\GatewayLogRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:GatewayLog');
    }
}
