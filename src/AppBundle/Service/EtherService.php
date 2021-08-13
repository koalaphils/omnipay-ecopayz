<?php

namespace AppBundle\Service;

use AppBundle\Model\CustomerPaymentOptions;

class EtherService
{
	private $http;
	private $jwtService;

	public function __construct(HttpService $http, JWTService $jwtService)
	{
		$this->http = $http;
		$this->jwtService = $jwtService;
	}

	public function getEtherTransaction($payload)
	{
		$jwt = $this->jwtService->getJWT([ 'username' => 'admin' ]);

		return $this->http->get("/api/v1/ether-transaction/usdt/details", [
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			],
			'query' => $payload
		]);
	}

	public function getEtherCurrentBlock()
	{
		$jwt = $this->jwtService->getJWT([ 'username' => 'admin' ]);

		return $this->http->get("/api/v1/ether-transaction/usdt/current-block", [
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			]
		]);
	}

	public function updateProcessedEtherTransactionStatus($hash)
	{
		$jwt = $this->jwtService->getJWT([ 'username' => 'admin' ]);

		return $this->http->put("/api/v1/ether-transaction/usdt/update/${hash}", [
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			]
		]);
	}
}