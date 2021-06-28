<?php

namespace ApiBundle\Validator\Bitcoin;

use ApiBundle\Request\Transaction\DepositRequest;
use ApiBundle\Request\Transaction\Meta\Bitcoin\BitcoinPayment;
use ApiBundle\Request\Transaction\Meta\Bitcoin\BitcoinRateDetail;
use ApiBundle\Request\Transaction\Product;
use AppBundle\Helper\NumberHelper;
use AppBundle\ValueObject\Number;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class BitcoinValidatorCallback
{
    private static $incorrectAdjustedRateMessage = 'Adjusted rate expecting {{ expectedRate }}, {{ value }} given.';
    private static $totalBitcoinNotInRangeMessage = 'Total bitcoin must be between {{ start }} and {{ end }}, {{ value }} given.';
    private static $incorectConvertedAmountMessage = 'Converted Amount must be {{ expectedAmount }}, {{ value }} given.';

    public static function validateConvertedAmount(BitcoinPayment $bitcoinDetail, ExecutionContextInterface $context, $payload): void
    {
        $currentPropertyPath = $context->getPropertyPath();
        $context->setNode($context->getValue(), $context->getObject(), $context->getMetadata(), 'products');

        $rate = new Number($bitcoinDetail->getRate());
        $products = $context->getRoot()->getProducts();
        foreach ($products as $key => $product) {
            /* @var $product Product */
            $bitcoin = $product->getMeta()->getPaymentDetails()['bitcoin']->getBitcoin();
            $amount = $product->getAmount();
            if (is_null($product->getAmount())) {
                continue;
            }
            $expectedAmount = $rate->times($bitcoin);
            if ($expectedAmount->notEqual($amount)) {
                $context
                    ->buildViolation(static::$incorectConvertedAmountMessage)
                    ->atPath('[' . $key . '].amount')
                    ->setParameter('{{ value }}', NumberHelper::toFloat($amount))
                    ->setParameter('{{ expectedAmount }}', $expectedAmount->toFloat())
                    ->addViolation();
            }
        }
        $context->setNode($context->getValue(), $context->getObject(), $context->getMetadata(), $currentPropertyPath);
    }

    public static function validateRate(string $adjustedRate, ExecutionContextInterface $context, $payload): void
    {
        $bitcoinPayment = $context->getObject();
        $request = $context->getRoot();
        $expectedRate = static::computeExpectedRate($request, $bitcoinPayment->getBlockchainRate(), $bitcoinPayment->getRateDetail());

        $expectedRate = new Number(Number::format($expectedRate->toString(), [ 'precision' => 2 ]));
        if (!$expectedRate->equals($adjustedRate)) {
            $context
                ->buildViolation(static::$incorrectAdjustedRateMessage)
                ->setParameter('{{ expectedRate }}', $expectedRate->toFloat())
                ->setParameter('{{ value }}', $adjustedRate)
                ->addViolation();
        }
    }

    public static function validateRangeAmount(BitcoinRateDetail $rateDetail, ExecutionContextInterface $context, $payload): void
    {
        $totalBitcoin = new Number(0);
        $request = $context->getRoot();
        /* @var $product \ApiBundle\Request\Transaction\Product */
        foreach ($request->getProducts() as $product) {
            if (!Number::isNumber($product->getMeta()->getPaymentDetails()['bitcoin']->getBitcoin())) {
                continue;
            }
            $totalBitcoin = $totalBitcoin->plus($product->getMeta()->getPaymentDetails()['bitcoin']->getBitcoin());
        }

        if ($rateDetail->getRangeStart() !== '' && $rateDetail->getRangeEnd() !== '') {
            if ($totalBitcoin->greaterThan($rateDetail->getRangeEnd()) || $totalBitcoin->lessThan($rateDetail->getRangeStart())) {
                $context
                    ->buildViolation(static::$totalBitcoinNotInRangeMessage)
                    ->setParameter('{{ start }}', $rateDetail->getRangeStart())
                    ->setParameter('{{ end }}', $rateDetail->getRangeEnd())
                    ->setParameter('{{ value }}', $totalBitcoin->toFloat())
                    ->addViolation();
            }
        }
    }

    private static function computeExpectedRate($request, string $blockchainRate, BitcoinRateDetail $rateDetail): Number
    {
        if ($request instanceof DepositRequest) {
            $expectedRate = Number::sub($blockchainRate, $rateDetail->getAdjustment());
        } else {
            $expectedRate = Number::add($blockchainRate, $rateDetail->getAdjustment());
        }

        return $expectedRate;
    }
}
