<?php

namespace AppBundle\Service;

use AppBundle\Model\CustomerPaymentOptions;
use AppBundle\Exceptions\CustomerPaymentOptionServiceException;
use Symfony\Component\HttpFoundation\RequestStack;
use Monolog\Logger;

class CustomerPaymentOptionService
{
	private $http;
	private $poService;
	private $jwtService;
	private $requestStack;
	private $logger;
	private $customerPaymentOptions;

	public function __construct(HttpService $http, PaymentOptionService $poService, JWTService $jwtService, RequestStack $requestStack, Logger $logger)
	{
		$this->http = $http;
		$this->poService = $poService;
		$this->jwtService = $jwtService;
		$this->requestStack = $requestStack;
		$this->logger = $logger;
	}

	public function getCustomerPaymentOptions(int $customerId): CustomerPaymentOptions
	{
		if ($this->customerPaymentOptions === null) {
			$jwt = $this->jwtService->getJWT(['username' => 'admin']);
			$payload =  $this->http->get("/api/v1/customer-payment-option/{$customerId}", [
				'headers' => [
					'Authorization' => "Bearer {$jwt}"
				]
			]);

			$this->customerPaymentOptions = new CustomerPaymentOptions($payload);
		}

		return $this->customerPaymentOptions;
	}

	public function checkAvailability(int $customerId, string $paymentOptionCode, string $field, string $value)
	{
		$jwt = $this->jwtService->getJWT(['username' => 'admin']);
		$queryString = http_build_query([
			'customer' => $customerId,
			'paymentOption' => $paymentOptionCode,
			'field' => $field,
			'value' => $value
		]);

		return $this->http->get("/api/v1/customer-payment-option/availability?{$queryString}", [
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			]
		]);
	}

	public function getCustomerPaymentOptionDetails($customerId, $paymentOptionCode, $transactionType, $fieldValues = [], $options = [])
	{
		try  {
			$paymentOption = $this->poService->getPaymentOption($paymentOptionCode);
			$customerPaymentOptions = $this->getCustomerPaymentOptions($customerId);

			$onRecord = $customerPaymentOptions->getActiveFields($paymentOptionCode, $transactionType);
			if (count($fieldValues) === 0) {
				$fieldValues = $onRecord;
			}

			// So we can start a clean slate for a particular set of fields
			if (isset($options['purge']) && $options['purge'] === true) {
				$customerPaymentOptions->purgeActiveFields($paymentOptionCode, $transactionType);
			}

			if (isset($options['timestamp']) && $options['timestamp'] === true) {
				$fieldValues['updatedAt'] = (new \DateTime())->format('yyyy-mm-dd');
			}
			
			$activeCpoFields = $customerPaymentOptions->getActiveFieldsForPaymentOptionOrCreateWhenNone(
				$paymentOption,
				$transactionType,
				$fieldValues,
				$options
			);

			if (empty($onRecord)) {
				$onRecord = $activeCpoFields;
			}

			$this->save($customerId, $customerPaymentOptions);
			$this->log($customerId, $customerPaymentOptions, $paymentOptionCode);
		} catch (\Exception $ex) {
			$this->logger->error('CUSTOMER PAYMENT OPTION SERVICE ERROR: '. $ex->getMessage() . $ex->getTraceAsString());
			$this->logger->error('TRANSACTION DURING THE ERROR' . $transactionType);
			throw new CustomerPaymentOptionServiceException('Error in getting customer payment options\' details.');
		}

		return [
			'onRecord' => $onRecord,
			'onTransaction' => $activeCpoFields,
		];
	}

	public function log($customerId, $customerPaymentOptions, $paymentOptionCode)
	{
		$jwt = $this->jwtService->getJWT();

		foreach ($customerPaymentOptions->getLogs() as $log) {
			$this->http->post("/api/v1/customer-payment-option/{$customerId}/log", [
				'json' => [
					'newValue' => $log['newValue'],
					'oldValue' => $log['oldValue'],
					'operation' => $log['operation'],
					'type' => 'member',
					'extra' => [
						'paymentOption' => $paymentOptionCode
					],
					'ip' => $this->getClientIp()
				],
				'headers' => [
					'Authorization' => "Bearer {$jwt}"
				]
			]);
		}
	}

	private function getClientIp()
	{
		$ips = array_merge(
			explode(',', str_replace(' ', '', $this->requestStack->getCurrentRequest()->server->get('HTTP_X_FORWARDED_FOR'))),
			explode(',', str_replace(' ', '', $this->requestStack->getCurrentRequest()->server->get('REMOTE_ADDR'))),
			$this->requestStack->getCurrentRequest()->getClientIps()
		);

		return implode(',', $ips);
	}

	public function save(int $customerId, CustomerPaymentOptions $customerPaymentOptions)
	{
		$jwt = $this->jwtService->getJWT();

		return $this->http->post("/api/v1/customer-payment-option/{$customerId}", [
			'json' => $customerPaymentOptions->getPayload(),
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			]
		]);
	}


	public function update(int $customerId, array $payload)
	{
		$jwt = $this->jwtService->getJWT();

		return $this->http->put("/api/v1/customer-payment-option/{$customerId}", [
			'json' => $payload,
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			]
		]);
	}


	public function search(string $search, array $filters)
	{
		$jwt = $this->jwtService->getJWT(['username' => 'admin']);
		$queryString = http_build_query([
			'search' => $search,
			'filters' => $filters
		]);
		return $this->http->get("/api/v1/customer-payment-option/search?{$queryString}", [
			'headers' => [
				'Authorization' => "Bearer {$jwt}"
			]
		]);
	}
}