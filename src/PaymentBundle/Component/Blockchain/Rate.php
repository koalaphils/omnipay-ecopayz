<?php

namespace PaymentBundle\Component\Blockchain;

use AppBundle\ValueObject\Number;
use PaymentBundle\Component\Blockchain\Exceptions\CurrencyRateException;

class Rate extends BlockchainComponent
{
    public const RATE_EUR = 'EUR';
    
    public const TICKER_PATH = '/ticker';
    public const TOBTC_PATH = '/tobtc';
    
    public function ticker(): array
    {
        return \GuzzleHttp\json_decode($this->get(self::TICKER_PATH)->getBody(), true);
    }
    
    public function fromBTC(string $currency, string $amount = '1'): string
    {
        $tickerData = $this->ticker();
        
        if (!array_key_exists($currency, $tickerData)) {
            throw new CurrencyRateException(sprintf('Currency %s was unable to convert from BTC', $currency));
        }
        
        $currencyAmount = (string) $tickerData[$currency]['last'];
        
        if ($amount === '1') {
            return $currencyAmount;
        }
        
        return Number::mul($amount, $currencyAmount)->toString();
    }
    
    public function toBTC(string $currency, string $amount = '1'): string
    {   
        return $this->get(self::TOBTC_PATH, ['currency' => $currency, 'value' => $amount])->getBody()->getContents();
    }
}
