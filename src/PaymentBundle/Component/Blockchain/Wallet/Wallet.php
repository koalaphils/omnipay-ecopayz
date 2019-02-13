<?php

namespace PaymentBundle\Component\Blockchain\Wallet;

use PaymentBundle\Component\Blockchain\BitcoinConverter;
use PaymentBundle\Component\Blockchain\BlockchainComponent;
use PaymentBundle\Component\Blockchain\BlockchainInterface;
use Psr\Http\Message\ResponseInterface;

class Wallet extends BlockchainComponent
{
    private const PAYMENT_PATH = '/merchant/{guid}/payment';
    private const SEND_TO_MANY_PATH = '/merchant/{guid}/sendmany';
    private const WALLET_BALANCE_PATH = '/merchant/{guid}/balance';
    private const LIST_ACTIVE_HD_ACCOUNTS_PATH = '/merchant/{guid}/accounts';
    private const LIST_HD_XPUBS_PATH = '/merchant/{guid}/accounts/xpubs';
    private const HD_ACCOUNT_PATH = '/merchant/{guid}/accounts/{xpub_or_index}';
    private const HD_ACCOUNT_BALANCE_PATH = '/merchant/{guid}/accounts/{xpub_or_index}/balance';

    private $walletUrl;

    public function payment(
        Credentials $credential,
        string $to,
        string $amount,
        string $from = '',
        string $fee = '',
        string $feePerByte = ''
    ): PaymentResponse {
        $path = str_replace('{guid}', $credential->getGuid(), self::PAYMENT_PATH);
        $query = [
            'password' => $credential->getPassword(),
            'to' => $to,
            'amount' => BitcoinConverter::convertToSatoshi($amount),
        ];

        if ($credential->hasSecondPassword()) {
            $query['second_password'] = $credential->getSecondPassword();
        }

        if ($from !== '') {
            $query['from'] = $from;
        }

        if ($fee !== '') {
            $query['fee'] = $fee;
        }

        if ($feePerByte !== '') {
            $query['fee_per_byte'] = $feePerByte;
        }

        $response = json_decode($this->get($this->getWholePath($path), $query)->getBody(), true);

        return PaymentResponse::create($response);
    }

    public function getWalletBalance(Credentials $credential): string
    {
        $path = str_replace('{guid}', $credential->getGuid(), self::WALLET_BALANCE_PATH);

        $response = json_decode(
            $this->get($this->getWholePath($path), ['password' => $credential->getPassword()])->getBody(),
            true
        );

        return (string) $response['balance'];
    }

    /**
     * @return Account[]
     */
    public function listActiveHdAccounts(Credentials $credential): array
    {
        $path = str_replace('{guid}', $credential->getGuid(), self::LIST_ACTIVE_HD_ACCOUNTS_PATH);

        $response = json_decode(
            $this->get($this->getWholePath($path), ['password' => $credential->getPassword()])->getBody(),
            true
        );

        $accounts = [];
        foreach ($response as $account) {
            $accounts[] = Account::create($account);
        }

        return $accounts;
    }

    /**
     * @return string[]
     */
    public function listXPubs(Credentials $credential): array
    {
        $path = str_replace('{guid}', $credential->getGuid(), self::LIST_HD_XPUBS_PATH);

        $response = json_decode(
            $this->get($this->getWholePath($path), ['password' => $credential->getPassword()])->getBody(),
            true
        );

        return $response;
    }

    public function getSingleAccount(Credentials $credential, string $xpubOrIndex): Account
    {
        $path = str_replace('{guid}', $credential->getGuid(), self::HD_ACCOUNT_PATH);
        $path = str_replace('{xpub_or_index}', $xpubOrIndex, $path);

        $response = json_decode(
            $this->get($this->getWholePath($path), ['password' => $credential->getPassword()])->getBody(),
            true
        );

        return Account::create($response);
    }

    public function getAccountBalance(Credentials $credential, string $xpubOrIndex): string
    {
        $path = str_replace('{guid}', $credential->getGuid(), self::HD_ACCOUNT_BALANCE_PATH);
        $path = str_replace('{xpub_or_index}', $xpubOrIndex, $path);

        $response = json_decode(
            $this->get($this->getWholePath($path), ['password' => $credential->getPassword()])->getBody(),
            true
        );

        return (string) $response['balance'];
    }

    public function __construct(BlockchainInterface $blockchain, string $walletUrl)
    {
        parent::__construct($blockchain);
        $this->walletUrl = $walletUrl;
    }

    protected function get(string $path, array $query = [], array $params = []): ResponseInterface
    {
        if ($this->getBlockchain()->getApiKey() !== '') {
            $query['api_code'] = $this->getBlockchain()->getApiKey();
        }

        return parent::get($path, $query, $params);
    }

    protected function post(string $path, array $postData = array(), array $params = array()): ResponseInterface
    {
        if ($this->getBlockchain()->getApiKey() !== '') {
            $postData['api_code'] = $this->getBlockchain()->getApiKey();
        }

        return parent::post($path, $postData, $params);
    }

    private function getWholePath(string $path): string
    {
        return trim($this->walletUrl, '\/') . $path;
    }
}
