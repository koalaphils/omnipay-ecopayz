<?php

namespace PaymentBundle\Component\Blockchain\XPubScanner\Client;

use PaymentBundle\Component\Blockchain\XPubScanner\XPubReceiverAddress;

interface XPubScannerClientInterface
{
    public function getAddressInIndex(string $xpub, int $index): XPubReceiverAddress;
}
