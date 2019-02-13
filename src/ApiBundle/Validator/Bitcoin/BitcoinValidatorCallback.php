<?php

namespace ApiBundle\Validator\Bitcoin;

use ApiBundle\Model\Bitcoin\BitcoinPayment;
use ApiBundle\Model\Bitcoin\BitcoinRateDetail;
use ApiBundle\Model\Bitcoin\BitcoinSubTransactionDetail;
use AppBundle\Helper\NumberHelper;
use AppBundle\ValueObject\Number;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use function dump;

class BitcoinValidatorCallback
{
    private static $incorrectAdjustedRateMessage = 'Adjusted rate expecting {{ expectedRate }}, {{ value }} given.';
    private static $totalBitcoinNotInRangeMessage = 'Total bitcoin must be between {{ start }} and {{ end }}, {{ value }} given.';
    private static $incorectConvertedAmountMessage = 'Converted Amount must be {{ expectedAmount }}, {{ value }} given.';

    public static function validateConvertedAmount(BitcoinPayment $bitcoinDetail, ExecutionContextInterface $context, $payload): void
    {
        $context->setNode($context->getRoot()->getData(), $context->getRoot()->getData(), null, '');

        $rate = new Number($bitcoinDetail->getRate());
        $subTransactionsForm = $context->getRoot()->get('subTransactions');
        foreach ($subTransactionsForm->getIterator() as $index => $subTransactionForm) {
            $bitcoin = $subTransactionForm->getData()->getPaymentDetails()->getBitcoin();
            $amount = $subTransactionForm->getData()->getAmount();
            if (is_null($subTransactionForm->getData()->getAmount())) {
                continue;
            }
            $expectedAmount = $rate->times($bitcoin);
            if ($expectedAmount->notEqual($amount)) {
                $context
                    ->buildViolation(static::$incorectConvertedAmountMessage)
                    ->atPath('children[subTransactions].data[' . $index . '].amount')
                    ->setParameter('{{ value }}', NumberHelper::toFloat($amount))
                    ->setParameter('{{ expectedAmount }}', $expectedAmount->toFloat())
                    ->addViolation();
            }
        }
    }

    public static function validateRate(string $adjustedRate, ExecutionContextInterface $context, $payload): void
    {
        $bitcoinPayment = $context->getObject();
        $expectedRate = static::computeExpectedRate($bitcoinPayment->getBlockchainRate(), $bitcoinPayment->getRateDetails());

        $expectedRate = new Number(Number::format($expectedRate->toString(), [ 'precision' => 2 ]));
        if (!$expectedRate->equals($adjustedRate)) {
            $context
                ->buildViolation(static::$incorrectAdjustedRateMessage)
                ->setParameter('{{ expectedRate }}', $expectedRate->toFloat())
                ->addViolation();
        }
    }

    public static function validateRangeAmount(BitcoinRateDetail $rateDetail, ExecutionContextInterface $context, $payload): void
    {
        $totalBitcoin = new Number(0);
        $transaction = $context->getRoot()->getData();
        foreach ($transaction->getSubTransactions() as $subTransaction) {
            if (!Number::isNumber($subTransaction->getPaymentDetails()->getBitcoin())) {
                continue;
            }
            $totalBitcoin = $totalBitcoin->plus($subTransaction->getPaymentDetails()->getBitcoin());
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

    private static function computeExpectedRate(string $blockchainRate, BitcoinRateDetail $rateDetail): Number
    {
        $expectedRate = Number::sub($blockchainRate, $rateDetail->getAdjustment());

        return $expectedRate;
    }
}
