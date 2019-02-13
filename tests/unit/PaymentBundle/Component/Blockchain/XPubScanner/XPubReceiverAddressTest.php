<?php

namespace PaymentBundle\Component\Blockchain\XPubScanner;

use Codeception\Test\Unit;

class XPubReceiverAddressTest extends Unit
{
    public function testCreate()
    {
        $address = XPubReceiverAddress::create([
            'index' => '1',
            'address' => '19DrZKeSaxfGPYFSDZRv72hyuWbfrVVy8q',
            'balance' => '0.023',
            'used' => true,
        ]);

        $this->assertSame(1, $address->getIndex());
        $this->assertSame('19DrZKeSaxfGPYFSDZRv72hyuWbfrVVy8q', $address->getAddress());
        $this->assertSame('0.023', $address->getBalance());
        $this->assertSame(true, $address->isUsed());
    }
}
