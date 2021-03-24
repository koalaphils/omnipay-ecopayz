<?php

namespace PaymentBundle\Component\Blockchain\XPubScanner;

use PaymentBundle\Component\Blockchain\XPubScanner\Client\XPubScannerClientInterface;

class XPubScanner
{
    /**
     * @var XPubScannerClientInterface
     */
    private $client;

    public function getAddressInIndex(string $xpub, int $index): XPubReceiverAddress
    {
        return $this->client->getAddressInIndex($xpub, $index);
    }

    public function __construct(XPubScannerClientInterface $client)
    {
        $this->client = $client;
    }
}
