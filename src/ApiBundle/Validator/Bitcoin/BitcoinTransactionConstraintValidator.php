<?php

namespace ApiBundle\Validator\Bitcoin;

use ApiBundle\Request\Transaction\Meta\Bitcoin\BitcoinPayment;
use ApiBundle\Repository\TransactionRepository;
use ApiBundle\Request\Transaction\Meta\Bitcoin\BitcoinProductPayment;
use ApiBundle\Request\Transaction\Product;
use AppBundle\Helper\NumberHelper;
use AppBundle\ValueObject\Number;
use DbBundle\Entity\Transaction;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class BitcoinTransactionConstraintValidator extends ConstraintValidator
{
    private $transactionRepository;
    /**
     *
     * @var Number
     */
    private $maxBitcoinDeposit;
    /**
     *
     * @var Number
     */
    private $minBitcoinDeposit;

    public function validate($value, Constraint $constraint)
    {
        if (!($value instanceof BitcoinPayment)
            || !($constraint instanceof BitcoinTransactionConstraint)
        ) {
            return;
        }

        $request = $this->context->getRoot();

        $transactionType = '';
        if ($constraint->getType() === 'deposit') {
            $transactionType = Transaction::TRANSACTION_TYPE_DEPOSIT;
        } elseif ($constraint->getType() === 'withdraw') {
            $transactionType = Transaction::TRANSACTION_TYPE_WITHDRAW;
        }

        $transactionWithBitcoin = $this->transactionRepository->findActiveBitcoinTransactionByMemberId($request->getMemberId(), $transactionType);
        if ($transactionWithBitcoin instanceof Transaction) {
            $this->context->buildViolation($constraint->getMessage())->addViolation();
        }

        $totalAmount = $this->getTotalBitcoin($request->getProducts());

        if (Number::isNumber($totalAmount)) {
            if ($this->maxBitcoinDeposit->lessThan($totalAmount) || $this->minBitcoinDeposit->greaterThan($totalAmount)) {
                $this->context
                    ->buildViolation($constraint->getMinMaxDepositMessage())
                    ->setParameters([
                        '{{ min }}' => NumberHelper::removeExtraZero($this->minBitcoinDeposit->toString()),
                        '{{ max }}' => NumberHelper::removeExtraZero($this->maxBitcoinDeposit->toString()),
                    ])
                    ->addViolation()
                ;
            }
        }
    }

    /**
     * @param Product[] $products
     * @return string
     * @throws \Exception
     */
    public function getTotalBitcoin(array $products): string
    {
        $total = new Number('0');
        foreach ($products as $product) {
            $bitcoinDetails = $product->getMeta()->getPaymentDetails()['bitcoin'] ?? null;
            if ($bitcoinDetails instanceof BitcoinProductPayment) {
                if (Number::isNumber($bitcoinDetails->getBitcoin())) {
                    $total = $total->plus($bitcoinDetails->getBitcoin());
                }
            }
        }

        return $total->toString();
    }

    public function setMinBitcoinDeposit(string $amount): void
    {
        $this->minBitcoinDeposit = new Number($amount);
    }

    public function setMaxBitcoinDeposit(string $amount): void
    {
        $this->maxBitcoinDeposit = new Number($amount);
    }

    public function __construct(TransactionRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }
}
