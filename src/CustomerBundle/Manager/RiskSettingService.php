<?php

namespace CustomerBundle\Manager;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Manager\AbstractManager;
use AppBundle\Exceptions\FormValidationException;

use AppBundle\Event\GenericEntityEvent;
use CustomerBundle\Events;
use DbBundle\Entity\AuditRevisionLog;
use DbBundle\Entity\Product;
use DbBundle\Entity\ProductRiskSetting;
use DbBundle\Entity\RiskSetting;
use DbBundle\Repository\CustomerRepository;

class RiskSettingService extends AbstractManager
{
    public function filter(array $params = []): array
    {
        $result = [];

        if (array_get($params, 'datatable', 0)) {
            $orders = array_get($params, 'orders', [['column' => 'id', 'dir' => 'asc']]);
            $limit = array_get($params, 'limit', 10);
            if (array_has($params, 'page') && !array_has($params, 'start')) {
                $offset = ((int) $params['page'] - 1) * $limit;
            } else {
                $offset = array_get($params, 'start', 0);
            }

            $filteredTotal = $this->getRepository()->total($params);
            $total = $this->getRepository()->total();

            $result = [
                'recordsFiltered' => $filteredTotal,
                'recordsTotal' => $total,
            ];

            $result['data'] = $this->getRepository()->filter($params, $orders, $limit, $offset);
            $result['draw'] = $params['draw'];
        } elseif (array_get($params, 'select2', 0)) {
            $result['items'] = $this->getRepository()->filter(['isActive' => true]);
            $result['items'] = array_map(function($item) {
                $obj = [];

                $obj['text'] = $item->getRiskId();
                $obj['id'] = $item->getResourceId();

                return $obj;
            }, $result['items']);

            $result['recordsFiltered'] = $this->getRepository()->total($params);
        } else {
            $result = $this->getRepository()->filter([]);
            $result = array_map(function($riskSetting) {
                $obj = [];

                $obj['text'] = $riskSetting->getRiskId();
                $obj['id'] = $riskSetting->getResourceId();

                return $obj;
            }, $this->getRepository()->filter([]));
        }

        return $result;
    }

    public function getActiveRiskSettings(): array
    {
        $result = array_map(function($riskSetting) {
            $obj = [];

            $obj['text'] = $riskSetting->getRiskId();
            $obj['id'] = $riskSetting->getResourceId();

            return $obj;
        }, $this->getRepository()->filter(['isActive' => true]));

        return $result;
    }

    public function suspend(int $riskSettingId)
    {
        $riskSetting = $this->getRepository()->find($riskSettingId);

        if (!$this->canSuspend($riskSetting)) {
            $message = [
                'type'      => 'error',
                'title'     => 'Cannot suspend!',
                'message'   => 'Some members are using this Risk Setting.',
            ];

            return $message;
        }
       
        if (is_null($riskSetting)) {
            throw new \Doctrine\ORM\NoResultException();
        } else if ($riskSetting->isActive()) {
            $riskSetting->suspend();
            $this->getRepository()->save($riskSetting);
            $message = [
                'type'      => 'success',
                'title'     => $this->getTranslator()->trans('notification.riskSetting.suspended.title', [], 'CustomerBundle'),
                'message'   => $this->getTranslator()->trans('notification.riskSetting.suspended.message', ['%riskSettingRiskId%' => $riskSetting->getRiskId() ], 'CustomerBundle'),
            ];

            return $message;
        } else {
            throw new \Exception('Risk Setting is already suspended', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    protected function canSuspend(RiskSetting $riskSetting): bool
    {
        $repo = $this->getCustomerRepository();
        $resourceId = $riskSetting->getResourceId();

        $hasSomeMemberUsingRiskSetting = $repo->hasSomeMemberUsingRiskSetting($resourceId);

        return !$hasSomeMemberUsingRiskSetting;
    }

    public function activate(int $riskSettingId)
    {
        $riskSetting = $this->getRepository()->find($riskSettingId);
        if (is_null($riskSetting)) {
            throw new \Doctrine\ORM\NoResultException();
        } else if ($riskSetting->isSuspended()) {
            $riskSetting->activate();
            $this->getRepository()->save($riskSetting);
            $message = [
                'type'      => 'success',
                'title'     => $this->getTranslator()->trans('notification.riskSetting.activated.title', [], 'CustomerBundle'),
                'message'   => $this->getTranslator()->trans('notification.riskSetting.activated.message', ['%riskSettingRiskId%' => $riskSetting->getRiskId() ], 'CustomerBundle'),
            ];

            return $message;
        } else {
            throw new \Exception('Customer Profiling is already activated', JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function saveRiskSetting(RiskSetting $riskSetting): void
    {
        $auditManager = $this->get('audit.manager');
        $em = $this->getDoctrine()->getManager();

        $auditLogDetails = $this->createAuditDetails($riskSetting);

        if (!$riskSetting->hasBeenPersisted()) {
            $auditManager->audit($riskSetting, AuditRevisionLog::OPERATION_CREATE, null, $auditLogDetails);
        } else {
            $auditManager->audit($riskSetting, AuditRevisionLog::OPERATION_UPDATE, null, $auditLogDetails); 
        }

        $this->dispatchEvent(Events::RISK_SETTING_SAVE, new GenericEntityEvent($riskSetting));
    }

    protected function createAuditDetails(RiskSetting $riskSetting): array
    {
        $original = $riskSetting->getOriginal();

        $auditLogDetails = [
            'riskId' => [$original->getRiskId(), $riskSetting->getRiskId()],
            'isActive' => [$original->getIsActive(), $riskSetting->getIsActive()],
            'productRiskSettings' => [],
        ];

        $originalProductRiskSettings = $original->getProductRiskSettings();
        $auditLogDetails['productRiskSettings'][] = [];
        foreach ($originalProductRiskSettings as $productRiskSetting) {
            $auditLogDetails['productRiskSettings'][0][] = [$productRiskSetting->getProductName() => $productRiskSetting->getRiskSettingPercentage()];
        };

        $auditLogDetails['productRiskSettings'][] = [];
        foreach ($riskSetting->getProductRiskSettings() as $productRiskSetting) {
            $auditLogDetails['productRiskSettings'][1][] = [$productRiskSetting->getProductName() => $productRiskSetting->getRiskSettingPercentage()];
        };

        return $auditLogDetails;
    }

    public function getRiskSetting(int $id): RiskSetting
    {
        $repo = $this->getRepository();

        return $repo->find($id);
    }

    public function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:RiskSetting');
    }

    public function getProductRiskSettingRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:ProductRiskSetting');
    }

    public function getCustomerRepository(): CustomerRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:Customer');
    }
}
