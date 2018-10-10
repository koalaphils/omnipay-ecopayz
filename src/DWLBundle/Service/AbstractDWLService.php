<?php

namespace DWLBundle\Service;

use AppBundle\Manager\SettingManager;
use DbBundle\Entity\CustomerProduct;
use DbBundle\Entity\DWL;
use DbBundle\Repository\CustomerProductRepository;
use Doctrine\ORM\EntityManager;
use DWLBundle\Manager\DWLManager;
use DWLBundle\Repository\TransactionRepository;
use LogicException;
use MediaBundle\Manager\MediaManager;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TransactionBundle\Manager\TransactionManager;
use TransactionBundle\WebsocketTopics;
use UserBundle\Manager\UserManager;

abstract class AbstractDWLService
{
    use ContainerAwareTrait;

    protected $logger = null;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function updateProcess(DWL $dwl, int $process, int $total)
    {
        $fileName = sprintf('dwl/progress_%s_v_%s.json', $dwl->getId(), $dwl->getVersion());
        if (!$this->getMediaManager()->isFileExists($fileName)) {
            $this->getMediaManager()->createFile($fileName);
        }
        $fileName = $this->getMediaManager()->getFilePath($fileName);
        $updatedAt = $dwl->getEncodedUpdatedAt();
        file_put_contents($fileName, json_encode(['_v' => $updatedAt, 'status' => $dwl->getStatus(), 'process' => $process, 'total' => $total]));

        $publishData = $this->generateDataForUpdateProcess($dwl, $process, $total);
        $this->updateDWLProcess($publishData);
    }

    protected function updateDWLProcess(array $data): void
    {
        $encodedData = json_encode($data);
        $encodedData = "'" . $encodedData . "'";

        $command = [
            'curl -H "Content-Type: application/json"',
            '-d',
            $encodedData,
            $this->container->getParameter('websocket.wamp') . '/pub &'
        ];

        $this->logger->info(implode(' ', $command));
        shell_exec(implode(' ', $command));
    }

    protected function generateDataForUpdateProcess(DWL $dwl, int $process, int $total): array
    {
        return [
            'topic' => WebsocketTopics::TOPIC_DWL_UPDATE_PROCESS,
            'args' => [[
                '_v' => $dwl->getEncodedUpdatedAt(),
                'status' => $dwl->getStatus(),
                'process' => $process,
                'total' => $total,
                'id' => $dwl->getId(),
                'date' => $dwl->getDate()->format('Y-m-d'),
                'product' => [
                    'id' => $dwl->getProduct()->getId(),
                    'code' => $dwl->getProduct()->getCode(),
                    'name' => $dwl->getProduct()->getName(),
                ],
                'currency' => [
                    'id' => $dwl->getCurrency()->getId(),
                    'code' => $dwl->getCurrency()->getCode(),
                    'name' => $dwl->getCurrency()->getName(),
                ],
                'canBeExported' => $dwl->canBeExported(),
                'version' => $dwl->getVersion(),
            ]]
        ];
    }

    protected function updateDWL(DWL $dwl, $status): void
    {
        $dwl->setStatus($status);
        $this->getDWLManager()->save($dwl);
    }

    protected function log(string $message, string $type = "info", array $context = [])
    {
        if (!$this->hasLogger()) {
            return;
        }
        if ($type === 'alert') {
            $this->logger->alert($message, $context);
        } elseif ($type === 'critical') {
            $this->logger->critical($message, $context);
        } elseif ($type === 'debug') {
            $this->logger->debug($message, $context);
        } elseif ($type === 'emergency') {
            $this->logger->emergency($message, $context);
        } elseif ($type === 'error') {
            $this->logger->error($message, $context);
        } elseif ($type === 'info') {
            $this->logger->info($message, $context);
        } elseif ($type === 'notice') {
            $this->logger->notice($message, $context);
        } elseif ($type === 'warning') {
            $this->logger->warning($message, $context);
        }
    }

    protected function hasLogger(): bool
    {
        return $this->logger instanceof LoggerInterface;
    }

    protected function getMediaManager(): MediaManager
    {
        return $this->getService('media.manager');
    }

    protected function getDWLManager(): DWLManager
    {
        return $this->getService('dwl.manager');
    }

    protected function getSettingManager(): SettingManager
    {
        return $this->getService('app.setting_manager');
    }

    protected function getUserManager(): UserManager
    {
        return $this->getService('user.manager');
    }

    protected function getTransactionManager(): TransactionManager
    {
        return $this->getService('transaction.manager');
    }

    protected function getTransactionRepository(): TransactionRepository
    {
        return $this->getService('dwl.transaction_repository');
    }

    protected function getCustomerProductRepository(): CustomerProductRepository
    {
        return $this->getEntityManager()->getRepository(CustomerProduct::class);
    }

    protected function getEntityManager(string $name = 'default'): EntityManager
    {
        return $this->getDoctrine()->getManager($name);
    }

    protected function getDoctrine(): RegistryInterface
    {
        return $this->getService('doctrine');
    }

    protected function getService(string $serviceName)
    {
        return $this->container->get($serviceName);
    }
    
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }
    
    protected function getUser()
    {
        if (!$this->container->has('security.token_storage')) {
            throw new LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return;
        }

        return $user;
    }
}
