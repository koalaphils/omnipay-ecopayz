<?php

namespace AppBundle\DoctrineExtension\DBAL;

use Doctrine\DBAL\Connection as BaseConnection;

class Connection extends BaseConnection
{
    public function reconnect(): void
    {
        if (!$this->ping()) {
            $this->close();
            $this->connect();
        }
    }
}
