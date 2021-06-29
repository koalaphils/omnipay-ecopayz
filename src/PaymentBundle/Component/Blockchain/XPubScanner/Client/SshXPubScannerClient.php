<?php

namespace PaymentBundle\Component\Blockchain\XPubScanner\Client;

use PaymentBundle\Component\Blockchain\Exceptions\XpubReceiverIndexDoesNotExistsException;
use PaymentBundle\Component\Blockchain\XPubScanner\XPubReceiverAddress;
use phpseclib\Net\SSH2;

final class SshXPubScannerClient implements XPubScannerClientInterface
{
    /**
     * @var SSH2
     */
    private $ssh;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;


    /**
     * @var string
     */
    private $privateKeyPath;

    public function getAddressInIndex(string $xpub, int $index): XPubReceiverAddress
    {
        $ssh = $this->getSSH();
        $ssh->login($this->username, $this->password);
        $ssh->enablePTY();

        $env = 'PATH="$PATH:/usr/local/bin"';

        $ssh->exec(sprintf('%s xpub-scan %s -o %s --batch-size 1 --print-gap 1', $env, $xpub, $index));
        $output = $ssh->read(
            sprintf('/\[%s\]\s([A-Za-z0-9]{1,})\s(balance\=)\s([0-9]{1,}).*(\\r)/', $index),
            SSH2::READ_REGEX
        );
        $ssh->disconnect();

        $outputAsArray = explode("\r", $output);
        $firstAddress = $outputAsArray[3];

        if (preg_match('/^\[(?<index>' . $index . ')\]\s(?<address>[A-Za-z0-9]{1,})\s(balance\=)\s(?<balance>[0-9]{1,})(?<unused>.*)$/', $firstAddress, $matches) === 1) {
            return XPubReceiverAddress::create([
                'index' => $matches['index'],
                'address' => $matches['address'],
                'balance' => $matches['balance'],
                'used' => $matches['unused'] === '',
            ]);
        } else {
            throw XpubReceiverIndexDoesNotExistsException(sprintf('Index %d does not exists', $index));
        }
    }

    public function getSSH(): SSH2
    {
        if (is_null($this->ssh)) {
            $this->ssh = new SSH2($this->host, $this->port);
        }

        return $this->ssh;
    }

    public function __construct(string $host, int $port, string $username, string $password, string $privateKeyPath = '')
    {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->username = $username;
        $this->privateKeyPath = $privateKeyPath;
    }
}
