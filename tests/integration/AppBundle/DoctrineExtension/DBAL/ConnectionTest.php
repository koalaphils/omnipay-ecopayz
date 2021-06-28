<?php

namespace AppBundle\DoctrineExtension\DBAL;

class ConnectionTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    public function testReconnectAfterTimeout(): void
    {
        $expectedError = "An exception occurred while executing 'SELECT 2':\n\nPDO::query(): MySQL server has gone away";
        $hasError = false;
        $this->getConnection()->exec('SET SESSION wait_timeout=1');
        sleep(2);
        try {
            $this->getConnection()->executeQuery('SELECT 2')->execute();
        } catch (\Doctrine\DBAL\DBALException $e) {
            $hasError = true;
            $this->assertSame($expectedError, $e->getMessage());
            $this->getConnection()->reconnect();
            $result = $this->getConnection()->executeQuery('SELECT 2')->fetchColumn();
            $this->assertSame("2", $result);
        }
        $this->assertTrue($hasError);
        $this->getConnection()->close();
    }
    
    public function testDisconnectAndReconnect(): void
    {
        $this->assertTrue($this->getConnection()->isConnected());
        $this->getConnection()->close();
        $this->assertFalse($this->getConnection()->isConnected());
        $this->getConnection()->reconnect();
        $this->assertTrue($this->getConnection()->isConnected());
    }
    
    private function getConnection(): Connection
    {
        return $this->getModule('Doctrine2')->_getEntityManager()->getConnection();
    }
}
