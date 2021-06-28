<?php

namespace WebSocketBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use AppBundle\Manager\SettingManager;
use DbBundle\Entity\Transaction;

class TransactionListener
{
    use \Symfony\Component\DependencyInjection\ContainerAwareTrait;

    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if (!($entity instanceof Transaction)) {
            return;
        }

        process([
            $this->getConatinerParameter('php_command') . ' ' . $this->getRootDir() . '/console',
            'websocket:call',
            'counter.set',
            "'" . json_encode([0, $this->getSettingManager()->getSetting('counter')]) . "'",
        ]);

        process(
            [
                $this->getConatinerParameter('php_command') . ' ' . $this->getRootDir() . '/console',
                'websocket:publish',
                'notifications',
                "'" . json_encode([
                    'type' => 'transaction',
                    'id' => $entity->getId(),
                    'number' => $entity->getNumber(),
                    'status' => $entity->getStatus(),
                ]) . "'",
            ],
            $this->getLogDir() . '/' . 'websocket.log'
        );
    }

    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if (!($entity instanceof Transaction)) {
            return;
        }

        process(
            [
                $this->getConatinerParameter('php_command') . ' ' . $this->getRootDir() . '/console',
                'websocket:call',
                'counter.set',
                "'" . json_encode([0, $this->getSettingManager()->getSetting('counter')]) . "'",
            ],
            $this->getLogDir() . '/' . 'websocket.log'
        );

        process(
            [
                $this->getConatinerParameter('php_command') . ' ' . $this->getRootDir() . '/console',
                'websocket:publish',
                'notifications',
                "'" . json_encode([
                    'type' => 'transaction',
                    'id' => $entity->getId(),
                    'number' => $entity->getNumber(),
                    'status' => $entity->getStatus(),
                ]) . "'",
            ],
            $this->getLogDir() . '/' . 'websocket.log'
        );
    }

    private function getConatinerParameter($name)
    {
        return $this->container->getParameter($name);
    }

    private function getRootDir()
    {
        return $this->container->get('kernel')->getRootDir();
    }

    private function getLogDir()
    {
        return rtrim($this->container->get('kernel')->getLogDir(), " \t\n\r\0\x0B\/");
    }

    /**
     * Get setting manager
     *
     * @return \AppBundle\Manager\SettingManager
     */
    private function getSettingManager()
    {
        return $this->container->get('app.setting_manager');
    }
}
