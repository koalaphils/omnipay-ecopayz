<?php

namespace PaymentBundle\Component\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\ValueObject\Number;

use DbBundle\Entity\BitcoinRateSetting;

class BitcoinAdjustment
{
    private $latestBaseRate;
    private $bitcoinRateSettings;
    private $bitcoinConfig;

    public function __construct(array $bitcoinRateSettings)
    {
        $this->bitcoinRateSettings = $bitcoinRateSettings;
    } 

    public static function fromCache(string $cache): BitcoinAdjustment
    {
        $payload = json_decode($cache, true);
        $bitcoinRateSettings = array_map(function($setting) {
            $bitcoinRateSetting = new BitcoinRateSetting();
            
            $bitcoinRateSetting->setIsDefault($setting['is_default']);
            $bitcoinRateSetting->setRangeFrom($setting['range_start']);
            $bitcoinRateSetting->setRangeTo($setting['range_end']);
            $bitcoinRateSetting->setFixedAdjustment($setting['fixed_adjustment']);
            $bitcoinRateSetting->setPercentageAdjustment($setting['percent_adjustment']);

            return $bitcoinRateSetting;
        }, $payload['conversion_table']);

        $bitcoinAdjustment = new BitcoinAdjustment($bitcoinRateSettings);
        $bitcoinAdjustment->setLatestBaseRate($payload['latest_base_rate']);
        $bitcoinAdjustment->setBitcoinConfig($payload['bitcoin_config']);

        return $bitcoinAdjustment;
    }

    public function setLatestBaseRate(string $latestBaseRate): void
    {
        $this->latestBaseRate = $latestBaseRate;
    }

    public function getLatestBaseRate(): string
    {
        return $this->latestBaseRate;
    }

    public function setBitcoinConfig(array $bitcoinConfig): void
    {
        $this->bitcoinConfig = $bitcoinConfig;
    }

    public function getBitcoinConfig(): array
    {
        return $this->bitcoinConfig;
    }

    public function getAdjustedRate(string $btc): string
    {
        if (count($this->bitcoinRateSettings) < 1) {
            return $this->getLatestBaseRate();
        }

        $bitcoinRateSetting = array_values(array_filter($this->bitcoinRateSettings, function($setting) use ($btc) {
            return $setting->isDefault();
        }))[0];

        if ($bitcoinRateSetting->getFixedAdjustment() !== null) {
            return Number::sub($this->getLatestBaseRate(), $bitcoinRateSetting->getFixedAdjustment());
        } else {
            $adjustment  = Number::mul(Number::div($bitcoinRateSetting->getPercentageAdjustment(), 100), $this->getLatestBaseRate());

            return Number::add($adjustment, $this->getLatestBaseRate());
        }

        throw new \LogicException('Code should not go here.');
    }

    public function toArray(): array
    {
        return [
            'latest_base_rate' => $this->getLatestBaseRate(),
            'adjusted_base_rate' => $this->getAdjustedRate(1),
            'bitcoin_config' => $this->getBitcoinConfig(),
            'conversion_table' =>  array_map(function($setting) {
                return [
                    'range_start' => $setting->getRangeFrom(),
                    'range_end' => $setting->getRangeTo(),
                    'fixed_adjustment' => $setting->getFixedAdjustment(),
                    'percent_adjustment' => $setting->getPercentageAdjustment(),
                    'is_default' => $setting->getIsDefault(),
                ];
            }, $this->bitcoinRateSettings),
        ];
    }

    public function createWebsocketPayload(): string
    {
        return json_encode($this->toArray());
    }
}