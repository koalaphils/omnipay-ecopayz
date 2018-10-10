<?php

namespace TransactionBundle\Validator;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use DbBundle\Entity\Transaction;
use AppBundle\ValueObject\Number;

/**
 * Description of TransactionValidator.
 *
 * @author Cydrick Nonog <cydrick.nonog@zmtsys.com>
 */
class TransactionValidator
{
    public static function validate(Transaction $object, ExecutionContextInterface $context)
    {
        /*if (count($object->getSubTransactions()) === 0) {
            $context->buildViolation('You must add product')->atPath('subTransactions')->addViolation();
        }*/

        if ($object->isTransfer() || $object->isP2pTransfer()) {
            $totalFrom = new Number(0);
            $totalTo = new Number(0);
            foreach ($object->getSubTransactions() as $key => $subTransaction) {
                if ($subTransaction->isWithdrawal()) {
                    $totalFrom = $totalFrom->plus($subTransaction->getAmount());
                } elseif ($subTransaction->isDeposit()) {
                    $totalTo = $totalTo->plus($subTransaction->getAmount());
                }
            }
            if ($totalFrom->notEqual($totalTo)) {
                $context->buildViolation('The total amount of products did not match')
                    ->atPath('subTransactions['. $key .'].amount')
                    ->addViolation();
            }
        }
    }

    public static function validateSub($subtransaction, ExecutionContextInterface $context)
    {
        if ($subtransaction->isWithdrawal()) {
           // AC66-334
           // self::preventNegativeBalance($subtransaction, $context);
        }
    }

    private static function preventNegativeBalance($subtransaction, ExecutionContextInterface $context)
    {
        $customerProduct = $subtransaction->getCustomerProduct();
        if ($customerProduct instanceof \DbBundle\Entity\CustomerProduct && $customerProduct  !== null) {
            $amount = $subtransaction->getAmount();
            if (!(new Number($customerProduct->getBalance()))->minus($amount)->greaterThanOrEqual(0)) {
                $context->buildViolation(
                    'Not enough balance: (Current Balance: '
                    . Number::format($customerProduct->getBalance(), ['precision' => 2])
                    . ')'
                )->atPath('amount')->addViolation();
            }
        }
    }
}
