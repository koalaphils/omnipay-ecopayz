<?php

namespace AuditBundle\Manager;

use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\AuditRevision;
use DbBundle\Entity\AuditRevisionLog;
use DbBundle\Entity\GatewayTransaction;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\DWL;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class AuditManager extends AbstractManager
{
    public function getList($filters = null): array
    {
        $userIds = [];
        if (!empty(array_get($filters, 'type', []))) {
            $userType = $filters['type'] == AuditRevision::TYPE_MEMBER ? User::USER_TYPE_MEMBER : User::USER_TYPE_ADMIN;
            $userIdList = $this->getUserRepository()->findByType($userType);

            foreach ($userIdList as $value) {
                $userIds[] = intval($value["id"]);
            }
        }

        $filters['userIds'] = $userIds;

        $results = [];
        if (array_get($filters, 'datatable', 0)) {
            if (false !== array_get($filters, 'search.value', false)) {
                $filters['search'] = $filters['search']['value'];
            }
            $orders = (!array_has($filters, 'order')) ? [['column' => 'ar.audit_revision_timestamp', 'columnQB' => 'ar.timestamp', 'dir' => 'desc'], ['column' => 'ar.audit_revision_id', 'columnQB' => 'ar.id', 'dir' => 'desc']] : $filters['order'];
            $results['data'] = $this->getRepository()->getList($filters, $orders, \Doctrine\ORM\Query::HYDRATE_OBJECT);
            $results['draw'] = $filters['draw'];
            $results['type'] = $userType;
            $results['recordsFiltered'] = $this->getRepository()->getListFilterCount($filters);
            $results['recordsTotal'] = $this->getRepository()->getListAllCount($filters);
        } else {
            $results = $this->getRepository()->getList($filters);
        }

        return $results;
    }

    public function redirect($id): array
    {
        $auditRevisionLog = $this->getRepository()->find($id);

        return $this->getRouteFromClass($auditRevisionLog);
    }

    private function getRouteFromClass(GatewayLog $gatewayLog): array
    {
        $referenceClass = $gatewayLog->getDetail('reference_class');
        $options = [];

        if ($referenceClass == GatewayTransaction::class) {
            $identifier = $gatewayLog->getDetail('identifier');
            $entity = $this->getDoctrine()->getRepository($referenceClass)->find($identifier);

            $options = [
                'route' => 'gateway_transaction.update_page',
                'params' => [
                    'type' => GatewayTransaction::translateType($entity->getType(), true),
                    'id' => $identifier,
                ],
            ];
        }

        return $options;
    }

    public function audit(AuditInterface $entity, $operation, $category = null, $changedProperties = null)
    {
        $auditRev = $this->createRevision();
        $auditRevLog = $this->createRevisionLog($entity, $operation, $category, $changedProperties);
        $auditRev->addLog($auditRevLog);

        $this->save($auditRev);
    }

    public function createRevision(): \DbBundle\Entity\AuditRevision
    {
        $auditRev = new AuditRevision();
        $adminAccount = $this->getUser();

        if ((!$adminAccount instanceof User) && $this->isUnderTestEnvironment()) { // system is under test
            $adminAccount = $this->getEntityManager()->getRepository(User::class)->findAdminByUsername('admin');
            $auditRev->setClientIp('127.0.0.1');
        } else {
            $request = $this->getContainer()->get('request_stack')->getCurrentRequest();
            if ($request instanceof Request) {
                $headers = $request->headers->all();
                if (isset($headers['x-forwarded-for']) && !empty($headers['x-forwarded-for'][0])) {
                    $auditRev->setClientIp($headers['x-forwarded-for'][0]);
                } else {
                    $auditRev->setClientIp($request->getClientIp());
                }
            } else {
                $auditRev->setClientIp('127.0.0.1');
            }
        }

        $auditRev->setUser($adminAccount);
        $auditRev->setTimestamp(new \DateTime());

        return $auditRev;
    }

    public function createRevisionLog(AuditInterface $entity, $operation, $category = null, $changedProperties = null, array $auditDetails = []): \DbBundle\Entity\AuditRevisionLog
    {
        $em = $this->getDoctrine()->getManager();
        $metadataFactory = $em->getMetadataFactory();
        $className = \Doctrine\Common\Util\ClassUtils::getClass($entity);

        if (!$metadataFactory->isTransient($className)) {
            $className = $metadataFactory->getMetadataFor($className)->getName();
        }

        $auditRevLog = new AuditRevisionLog();
        $auditRevLog->setCategory($category ? $category : $entity->getCategory());
        $auditRevLog->setOperation($operation);

        $details = [
            'label' => $entity->getLabel(),
            'class_name' => $className,
            'identifier' => $entity->getIdentifier(),
        ];

        if (!empty($changedProperties)) {
            $details['fields'] = $changedProperties;
        }

        $details['details'] = $auditDetails;

        $auditRevLog->setDetails($details);

        return $auditRevLog;
    }

    public function getLogDetails(): array
    {
        $logDetails = $this->getParameter('log_details');

        $logDetails['user']['type']['values'] = User::getTypesText();
        $logDetails['gatewayTransaction']['type']['values'] = GatewayTransaction::getTypeAsTexts();
        $logDetails['gatewayTransaction']['status']['values'] = GatewayTransaction::getStatusAsTexts();
        $logDetails['transaction']['type']['values'] = Transaction::getTypeAsTexts();

        $transactionStatusSetting = $this->getSettingManager()->getSetting('transaction.status');
        $transactionStatus = array_combine(array_keys($transactionStatusSetting), array_column($transactionStatusSetting, 'label'));

        $logDetails['transaction']['status']['values'] = $transactionStatus;

        return $logDetails;
    }

    protected function getSettingManager(): \AppBundle\Manager\SettingManager
    {
        return $this->get('app.setting_manager');
    }

    protected function getRepository(): \DbBundle\Repository\AuditRevisionRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:AuditRevision');
    }

    public function getUserRepository(): \DbBundle\Repository\UserRepository
    {
        return $this->getDoctrine()->getRepository('DbBundle:User');
    }
}
