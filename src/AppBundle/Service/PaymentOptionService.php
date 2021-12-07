<?php

namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\Session\Session;

class PaymentOptionService
{
	private $http;
	private $jwtService;

	const SKRILL = 'SKRILL';
	const NETELLER = 'NETELLER';
	const ECOPAYZ = 'ECOPAYZ';
	const BITCOIN = 'BITCOIN';
	const USDT = 'USDT';
	const PAYEER = 'PAYEER';
	const BANK = 'BANK';
	const OFFLINE = 'OFFLINE';

	public function __construct(HttpService $http, JWTService $jwtService)
	{
		$this->http = $http;
		$this->jwtService = $jwtService;
	}

	public function getPaymentOption(string $code)
	{
		$jwt = $this->jwtService->getJWT([ 'username' => 'admin' ]);

		return $this->http->get("/api/v1/payment-option/{$code}", [
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			]
		]);
	}

	public function getAllPaymentOptions()
	{
		$jwt = $this->jwtService->getJWT(['username' => 'admin']);

		return $this->http->get("/api/v1/payment-option", [
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			]
		]);
	}

	public function getReceiveAddress(string $code, string $callback)
	{
		$jwt = $this->jwtService->getJWT();
		$callback = urlencode($callback);
		return $this->http->get("/api/v1/payment-option/blockchain/address/{$code}?callbackUrl={$callback}", [
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			]
		]);
	}

	public function getBlockchainTransactionDetails(string $hash)
	{
		$jwt = $this->jwtService->getJWT();

		return $this->http->get("/api/v1/payment-option/blockchain/transaction/{$hash}", [
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			]
		]);
	}

	public function getBitcoinTransactionConfirmations(string $hash)
	{
		$jwt = $this->jwtService->getJWT();

		return $this->http->get("/api/v1/payment-option/blockchain/confirmations/{$hash}", [
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			]
		]);
	}

	public function getBitcoinTransactionAmountSent(string $hash, string $address)
	{
		$jwt = $this->jwtService->getJWT();

		return $this->http->get("/api/v1/payment-option/blockchain/transaction/{$hash}/{$address}", [
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			]
		]);
	}

	public function getBitcoinTransactions(string $address)
	{
		$jwt = $this->jwtService->getJWT();

		return $this->http->get("/api/v1/payment-option/blockchain/transactions/{$address}", [
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			]
		]);
	}

	public function getRandomUsdtReceiverAddress()
	{
		$jwt = $this->jwtService->getJWT([ 'username' => 'admin' ]);

		return $this->http->get("/api/v1/payment-option/ether/usdt-address", [
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			]
		]);
	}

	public static function isConfiguredToUseEmail(?string $paymentOptionCode)
	{
		return in_array($paymentOptionCode, [ PaymentOptionService::SKRILL, PaymentOptionService::NETELLER ]);
	}

	public static function isConfiguredToUseAccountId(?string $paymentOptionCode)
	{
		return in_array($paymentOptionCode, [ PaymentOptionService::BITCOIN, PaymentOptionService::USDT, PaymentOptionService::ECOPAYZ, PaymentOptionService::PAYEER ]);
	}

	// Backwards compatibility, to be removed soon once we removed payum.
	public static function getPaymentMode(string $paymentOptionCode)
	{
		if (in_array($paymentOptionCode, [PaymentOptionService::SKRILL, PaymentOptionService::NETELLER, PaymentOptionService::PAYEER])) {
			return 'offline';
		} else if (in_array($paymentOptionCode, [PaymentOptionService::BITCOIN, PaymentOptionService::USDT])) {
			return 'bitcoin';
		} else if ($paymentOptionCode === PaymentOptionService::ECOPAYZ) {
			return 'ecopayz';
		}
	}

	public static function getPaymentOptionsWithFreeCalendarMonthTag(): array
	{
		return [PaymentOptionService::SKRILL, PaymentOptionService::NETELLER, PaymentOptionService::ECOPAYZ];
	}
}