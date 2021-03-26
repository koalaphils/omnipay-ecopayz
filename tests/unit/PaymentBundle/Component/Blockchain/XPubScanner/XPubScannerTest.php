<?php

namespace PaymentBundle\Component\Blockchain\XPubScanner;

use Codeception\Test\Unit;
use PaymentBundle\Component\Blockchain\XPubScanner\Client\XPubScannerClientInterface;

class XPubScannerTest extends Unit
{
    public function testGetAddressInIndex()
    {
        $xpubScannerClientMock = $this->makeEmpty(XPubScannerClientInterface::class, ['getAddress' => function () {
            return new XPubReceiverAddress();
        }]);

        $xpubScanner = new XPubScanner($xpubScannerClientMock);
        $address = $xpubScanner->getAddressInIndex('xpub6CWiJoiwxPQni3DFbrQNHWq8kwrL2J1HuBN7zm4xKPCZRmEshc7Dojz4zMah7E4o2GEEbD6HgfG7sQid186Fw9x9akMNKw2mu1PjqacTJB2', 1);

        $this->assertInstanceOf(XPubReceiverAddress::class, $address);
    }
}
