<?php

namespace ApiBundle\Validator\Bitcoin;

use ApiBundle\Model\Bitcoin\BitcoinPayment;
use ApiBundle\Repository\TransactionRepository;
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

        $transaction = $value->getTransaction();

        $depositTransactionWithBitcoin = $this
            ->transactionRepository
            ->findActiveBitcoinTransaction(
                $transaction->getCustomer()
            );

        if ($depositTransactionWithBitcoin instanceof Transaction) {
            $this->context->buildViolation($constraint->getMessage())->addViolation();
        }

        $totalAmount = $value->getTotalBitcoin();

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
