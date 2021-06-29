<?php

namespace AppBundle\DoctrineExtension\DBAL;

use Doctrine\DBAL\Connections\MasterSlaveConnection as BaseConnection;
use Doctrine\DBAL\Exception\ConnectionException;

class Connection extends BaseConnection
{
    public function reconnect(): void
    {
        if (!$this->ping()) {
            $this->close();
            $this->connect();
        }
    }
    public function connect($connectionName = null)
    {
        try{
            return parent::connect($connectionName);
        }catch (ConnectionException $connectionException){
            if($connectionName !== 'master'){
                return parent::connect('master');
            }

            throw $connectionException;
        }
    }
}
