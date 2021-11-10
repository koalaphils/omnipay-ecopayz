<?php

namespace ProductIntegrationBundle\Exception\IntegrationException;

use PinnacleBundle\Component\Exceptions\PinnacleError;
use ProductIntegrationBundle\Exception\IntegrationException;
use Throwable;

class CreditIntegrationException extends IntegrationException
{
	public function __construct(string $body, string $code, Throwable $previous = null)
	{
		$this->_setMessage($body, $code, $previous);

		parent::__construct($body, $code, $previous);
	}

	private function _setMessage(&$body, $code, $previous)
	{
		if ($previous && $previous instanceof PinnacleError)
		{
			switch (intval($code))
			{
				case 308: $body = 'The amount should be a positive number.'; break;
				case 309: $body = 'The balance is not enouqh.'; break;
				case 310: $body = 'The balance exceeds the credit limit.'; break;
			}
		}
	}
}