<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace WebSocketBundle\Client;

use Gos\Bundle\WebSocketBundle\Client\ClientStorage as GosClientStorage;

class ClientStorage extends GosClientStorage
{
    /**
     * @return Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    public function saveClient($identifier, $user)
    {
        $serializedUser = serialize($user);

        $context = [
            'user' => $serializedUser,
        ];

        if ($user instanceof UserInterface) {
            $context['username'] = $user->getUsername();
        }

        $this->logger->debug(sprintf('SAVE CLIENT ' . $identifier), $context);

        try {
            $result = $this->driver->save($identifier, $serializedUser, $this->ttl);
        } catch (\Exception $e) {
            throw new StorageException(sprintf('Driver %s failed', get_class($this)), $e->getCode(), $e);
        }

        if (false === $result) {
            throw new StorageException('Unable save client');
        }
    }
}
