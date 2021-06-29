<?php

namespace GatewayBundle\Manager;

use AppBundle\Manager\AbstractManager;
use AppBundle\ValueObject\Number;
use DbBundle\Entity\Gateway;
use DbBundle\Entity\Interfaces\GatewayInterface;

/**
 * Gateway Manager.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class GatewayManager extends AbstractManager
{
    /**
     * @param type $filters
     *
     * @return type
     */
    public function getGatewayList($filters = null)
    {
        $results = [];
        if (array_get($filters, 'datatable', 0)) {
            if (false !== array_get($filters, 'search.value', false)) {
                $filters['search'] = $filters['search']['value'];
            }

            $results['data'] = $this->getRepository()->getGatewayList($filters);
            if (array_get($filters, 'route', 0)) {
                $results['data'] = array_map(function ($result) {
                    $data = [];
                    $data['gateway'] = $result;
                    $data['routes'] = [
                        'update' => $this->getRouter()->generate('gateway.update_page', ['id' => $data['gateway']['id']]),
                        'view' => $this->getRouter()->generate('gateway.view_page', ['id' => $data['gateway']['id']]),
                    ];

                    return $data;
                }, $results['data']);
            }
            $results['draw'] = $filters['draw'];
            $results['recordsFiltered'] = $this->getRepository()->getGatewayListFilterCount($filters);
            $results['recordsTotal'] = $this->getRepository()->getGatewayListAllCount();
        } elseif (array_get($filters, 'select2', 0)) {
            $results['items'] = array_map(function ($group) {
                $group['text'] = $group['name'];

                return $group;
            }, $this->getRepository()->getGatewayList($filters));
            $results['recordsFiltered'] = $this->getRepository()->getGatewayListFilterCount($filters);
        } else {
            $results = $this->getRepository()->getGatewayList($filters);
        }

        return $results;
    }

    public function auditManualBalance(Gateway $gateway)
    {
        $gatewayLogManager = $this->getGatewayLogManager();
        $finalAmount = new Number($gateway->getFinalAmount());

        if ($finalAmount->notEqual(0)) {
            $finalAmount = $finalAmount->toFloat();

            $gatewayLog = $gatewayLogManager->createLog(
                $finalAmount < 0 ? GatewayInterface::OPERATION_ADD : GatewayInterface::OPERATION_SUB,
                abs($finalAmount),
                $gateway->getPreviousBalance(),
                $gateway->getNameAndCurrencyCode(),
                $gateway->getCurrency(),
                $gateway,
                $gateway->getPaymentOptionEntity(),
                [
                    'identifier' => $gateway->getId(),
                    'reference_class' => Gateway::class,
                    'user' => $this->getUser()->getUsername(),
                ]
            );

            $gatewayLogManager->save($gatewayLog);
        }
    }

    public function findOneById(int $id): ?Gateway
    {
        return $this->getRepository()->findOneById($id);
    }

    /**
     * Get gateway repository.
     *
     * @return \DbBundle\Repository\GatewayRepository
     */
    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Gateway');
    }

    protected function getGatewayLogManager(): \GatewayTransactionBundle\Manager\GatewayLogManager
    {
        return $this->get('gateway_log.manager');
    }
}
