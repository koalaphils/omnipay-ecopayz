<?php

namespace PaymentBundle\Component\Blockchain\XPubScanner\Client;

use Codeception\Test\Unit;
use PaymentBundle\Component\Blockchain\XPubScanner\XPubReceiverAddress;

class SshXPubScannerClientTest extends Unit
{
    /**
     * @dataProvider getAddressInIndexDataProvider
     */
    public function testGetAddressInIndex(string $xpub, int $index, string $expectedAddress)
    {
        $xpubScannerClient = $this->getModule('Symfony2')->grabService('payment.xpub_scanner_ssh_client');
        $address = $xpubScannerClient->getAddressInIndex($xpub, $index);

        $this->assertInstanceOf(XPubReceiverAddress::class, $address);
        $this->assertSame($expectedAddress, $address->getAddress());
    }

    public function getAddressInIndexDataProvider()
    {
        yield [
            'xpub6CWiJoiwxPQni3DFbrQNHWq8kwrL2J1HuBN7zm4xKPCZRmEshc7Dojz4zMah7E4o2GEEbD6HgfG7sQid186Fw9x9akMNKw2mu1PjqacTJB2',
            1,
            '1DLQNwTXkR48aGWHwX8SzimtoecETuZByk',
        ];

        yield [
            'xpub6CWiJoiwxPQni3DFbrQNHWq8kwrL2J1HuBN7zm4xKPCZRmEshc7Dojz4zMah7E4o2GEEbD6HgfG7sQid186Fw9x9akMNKw2mu1PjqacTJB2',
            100,
            '1DHhSvUg9gkcdVBH6ybV7vRyPYUJ4VnwhK',
        ];
    }
}
