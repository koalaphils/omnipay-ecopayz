<?php

namespace AppBundle\Model;

use DbBundle\Entity\Transaction;

class CustomerPaymentOptions
{
	const LOG_OPERATION_CREATE = 1;
	const LOG_OPERATION_UPDATE = 2;
	const PAYMENT_OPTION_BTC = 'BITCOIN';
	const PAYMENT_OPTION_USDT = 'USDT';
	private $payload;
	private $logs = []; // Keeps track of changes
	private $determinantsFields = ['email', 'account_id'];

	public function __construct(array $payload)
	{
		$this->payload = $payload;
	}

	public function getPayload(): array
	{
		return $this->payload;
	}

	public function getLogs(): array
	{
		return $this->logs;
	}

	// Get all active set of fields for a particular PO.
	// If particular set of fields are not existing, create one instead.
	public function getActiveFieldsForPaymentOptionOrCreateWhenNone(array $paymentOption,
	                                                                string $transactionType,
	                                                                array $values = [],
	                                                                array $options = [])
	{
		$paymentOptionCode = $paymentOption['code'];
		$fieldSettings = $paymentOption['settings']['fields'];
		$linkFields = $paymentOption['settings']['linkFields'];

		$determinantField = $this->getDeterminantField($paymentOption);
		$activeCpoFields = $this->getActiveFields($paymentOptionCode, $transactionType);

		// Get inactive fields that has a determninant field value equal to the $value's determinant field
		$inactiveFields = $this->getFieldsByDeterminantValue($paymentOptionCode, $transactionType, $determinantField, $values[$determinantField]);

		// If replace option is true, usually on bitcoin and tether.
		if (count($activeCpoFields) > 0 && isset($options['replace']) && $options['replace'] === true) {
			return $this->replaceFields($activeCpoFields, $paymentOptionCode, $transactionType, $determinantField, $values);
		}

		$currentFields = $inactiveFields;
		if (count($activeCpoFields) === 0 || ($activeCpoFields[$determinantField] !== $values[$determinantField])) {
			return count($inactiveFields) > 0 ?
				$currentFields :
				$this->addFields(
					$paymentOptionCode,
					$transactionType,
					$values,
					[
						'fields' => $fieldSettings,
						'linkFields' => $linkFields
					]
				);
		}

		return $activeCpoFields;
	}

	public function replaceFields($activeCpoFields, $paymentOptionCode, $transactionType, $determinantField, $values)
	{
		$activeCpoDeterminantValue = $activeCpoFields[$determinantField];
		$index = null;
		foreach ($this->payload['payment_options_fields'][$paymentOptionCode] as $key => $fieldSet) {
			if ($fieldSet[$determinantField] === $activeCpoDeterminantValue && $fieldSet['transactionType'] == $transactionType) {
				$index = $key;
				break;
			}
		}

		$newFields = $activeCpoFields;
		foreach ($values as $key => $value) {
			$newFields[$key] = $value;
		}
		$this->auditLog(self::LOG_OPERATION_UPDATE, $activeCpoFields, $newFields);

		$this->payload['payment_options_fields'][$paymentOptionCode][$index] = $newFields;

		return $newFields;
	}

	public function getActiveFields(?string $code, string $transactionType)
	{
		$fields = [];
		$filterFields = function ($fields) use ($transactionType) {
			return $fields['active'] === true && $fields['transactionType'] == $transactionType;
		};

		if ($code) {
			if (isset($this->payload['payment_options_fields'][$code])) {
				$activeFields = array_values(array_filter($this->payload['payment_options_fields'][$code], $filterFields));
				$fields = count($activeFields) > 0 ? $activeFields[0] : [];
			}
		} else {
			foreach ($this->payload['payment_options_fields'] as $key => $value) {
				$activeFields = array_filter($value, $filterFields);
				if (count($activeFields) > 0) {
					$fields[$key] = array_values($activeFields)[0];
				}
			}
		}

		return $fields;
	}

	public function createFields(string $code, string $transactionType, $values = [], $poSettings = []): array
	{
		$fields = [];

		$newCpoFields = [];

		foreach ($poSettings['fields'] as $fieldsSetting) {
			$newCpoFields[$fieldsSetting['code']] = '';
		}

		$newCpoFields['active'] = false;
		if ($code == self::PAYMENT_OPTION_BTC || $code == self::PAYMENT_OPTION_USDT) {
			$newCpoFields['active'] = true;
		}

		$newCpoFields['transactionType'] = (int)$transactionType;

		foreach ($values as $key => $value) {
			$newCpoFields[$key] = $value;
		}

		$fields[] = $newCpoFields;

		if ($poSettings['linkFields']) {
			$copyFields = $newCpoFields;
			$copyFields['transactionType'] = $this->getOppositeTransactionType($transactionType);

			$fields[] = $copyFields;
		}

		return $fields;
	}

	public function addFields($code, string $transactionType, array $values = [], array $poSettings = [])
	{
		$newFields = $this->createFields($code, $transactionType, $values, $poSettings);

		foreach ($newFields as $fieldSet) {
			$this->payload['payment_options_fields'][$code][] = $fieldSet;
		}

		$key = array_search($transactionType, array_column($newFields, 'transactionType'));
		$transactionFields = $newFields[$key];

		$this->auditLog(self::LOG_OPERATION_CREATE, null, $transactionFields);

		return $transactionFields;
	}

	public function purgeActiveFields(string $code, string $transactionType)
	{
		if (isset($this->payload['payment_options_fields'][$code])) {
			$cpoFields = $this->payload['payment_options_fields'][$code];
			$filterFields = function ($fields) use ($transactionType) {
				return $fields['transactionType'] != $transactionType;
			};

			$newCpoFields = array_filter($cpoFields, $filterFields);
			$this->payload['payment_options_fields'][$code] = $newCpoFields;
		}
	}

	public function getFieldsByDeterminantValue(string $paymentOptionCode, string $transactionType, string $determinantField, string $fieldValue)
	{
		if (isset($this->payload['payment_options_fields'][$paymentOptionCode])) {
			$fields = $this->payload['payment_options_fields'][$paymentOptionCode];

			$fields = array_values(array_filter($fields, function ($fieldSet) use ($transactionType, $determinantField, $fieldValue) {
				return $fieldSet[$determinantField] === $fieldValue && $fieldSet['transactionType'] == $transactionType;
			}));

			return count($fields) > 0 ? $fields[0] : [];
		}

		return [];
	}

	private function getDeterminantField($paymentOption): string
	{
		$fields = $paymentOption['settings']['fields'];
		if (count($fields) > 0) {
			return $fields[0]['code'];
		}

		throw new \LogicException('No determinant field.');
	}

	private function getOppositeTransactionType($transactionType)
	{
		return $transactionType == Transaction::TRANSACTION_TYPE_DEPOSIT ? Transaction::TRANSACTION_TYPE_WITHDRAW : Transaction::TRANSACTION_TYPE_DEPOSIT;
	}

	private function auditLog($operation, $oldValue, $newValue): void
	{
		$this->logs[] = [
			'operation' => $operation,
			'oldValue' => $oldValue,
			'newValue' => $newValue
		];
	}
}