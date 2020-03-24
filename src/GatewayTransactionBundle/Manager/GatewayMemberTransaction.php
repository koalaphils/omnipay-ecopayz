<?php

declare(strict_types = 1);

namespace GatewayTransactionBundle\Manager;

use AppBundle\ValueObject\Number;
use DbBundle\Entity\Gateway;
use DbBundle\Entity\Interfaces\GatewayInterface;
use DbBundle\Entity\Transaction;
use Doctrine\ORM\EntityManager;

class GatewayMemberTransaction
{
    /**
     * @var GatewayLogManager
     */
    private $gatewayLogManager;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(GatewayLogManager $gatewayLogManager, EntityManager $entityManager)
    {
        $this->gatewayLogManager = $gatewayLogManager;
        $this->entityManager = $entityManager;
    }

    public function updateIdentifierByNumberAndClass(string $class, string $number, string $identifier): void
    {
        $gatewayLogs = $this->gatewayLogManager->getGatewayLogsByNumberAndClass($class, $number);
        foreach ($gatewayLogs as $gatewayLog) {
            if ($gatewayLog->getReferenceIdentifier() === null || $gatewayLog->getReferenceIdentifier() === 'null') {
                $gatewayLog->setDetail('identifier', $identifier);
                $this->entityManager->persist($gatewayLog);
                $this->entityManager->flush($gatewayLog);
            }
        }
    }

    public function processMemberTransaction(Transaction $transaction): void
    {
        dump('PROCESS GATEWAY');
        $gatewayLog = $this->gatewayLogManager->findLastGatewayLogByClassAndNumberOrIdentifier(Transaction::class, (string) $transaction->getId(), $transaction->getNumber());
        $gateway = $transaction->getGateway();
        $isDeclined = $transaction->getStatus() === Transaction::TRANSACTION_STATUS_DECLINE;

        if ($gateway === null) {
            return;
        }

        $details = $gateway->getDetails();

        if ($isDeclined) {
            if ($transaction->isDeposit()) {
                $methods = array_get($details, 'methods.withdraw');
            } else if ($transaction->isWithdrawal()) {
                $methods = array_get($details, 'methods.deposit');
            }
        } else {
            $methods = array_get($details, 'methods.' . $this->getType($transaction->getType(), true));
        }
        
        if ($transaction->isBonus()) {
            $vars = [['var' => 'x', 'value' => 'total_amount']];
            $equation = $gateway->getBalance() . '-x';
        } else {
            $vars = array_get($methods, 'variables', []);
            $equation = $gateway->getBalance() . array_get($methods, 'equation');
        }

        $predefineValues = $transaction->getDetail('summary', []);
        $variables = [];
        foreach ($vars as $var) {
            $variables[$var['var']] = $var['value'];
        }
        $oldBalance = $gateway->getBalance();
        $newBalance = $this->processEquation($equation, $variables, $predefineValues) . '';
        $gatewayTotal = (new Number($newBalance))->minus($oldBalance);
        $transaction->setGatewayComputedAmount($gatewayTotal->__toString());
        $transaction->getGateway()->setBalance($newBalance);

        $this->auditGateway($transaction, $gateway, $oldBalance, $newBalance);
        $this->entityManager->persist($transaction->getGateway());
        $this->entityManager->flush($transaction->getGateway());
    }

    public function voidMemberTransaction(Transaction $transaction): void
    {
        $gatewayLog = $this->gatewayLogManager->findLastGatewayLogByClassAndNumberOrIdentifier(Transaction::class, (string) $transaction->getId(), $transaction->getNumber());

        if ($gatewayLog === null) {
            return;
        } elseif ($transaction->isBonus() && !$gatewayLog->isWithdraw()) {
            return;
        } elseif (!$transaction->isBonus() && $transaction->getType() != $gatewayLog->getType()) {
            return;
        }

        $gateway = $transaction->getGateway();
        if ($gateway) {
            if ($transaction->isBonus()) {
                $method = ['equation' => '-x', 'variables' => [['var' => 'x', 'value' => 'total_amount']]];
            } else {
                $method = $gateway->getDetail('methods.' . $this->getType($transaction->getType(), true));
            }
            $equation = array_get($method, 'equation');

            switch ($equation[0]) {
                case '+':
                    $equation[0] = '-';
                    break;
                case '-':
                    $equation[0] = '+';
                    break;
                case '*':
                    $equation[0] = '/';
                    break;
                case '/':
                    $equation[0] = '*';
                    break;
            }

            $equation = $gateway->getBalance() . $equation;

            $variables = [];
            foreach (array_get($method, 'variables') as $var) {
                $variables[$var['var']] = $var['value'];
            }
            $predefineValues = $transaction->getDetail('summary', []);
            $newBalance = $this->processEquation($equation, $variables, $predefineValues) . '';
            $this->auditGateway($transaction, $gateway, $gateway->getBalance(), $newBalance);
            $transaction->getGateway()->setBalance($newBalance);
            $transaction->setDetail('paymentGateway.voided', true);

            $this->entityManager->persist($transaction->getGateway());
            $this->entityManager->flush($transaction->getGateway());
        }
    }

    protected function processEquation($equation, $variables = [], $predefineValues = []): Number
    {
        $variables = array_map(function ($value) use ($predefineValues) {
            return array_get($predefineValues, $value, $value);
        }, $variables);

        $value = Number::parseEquation($equation, $variables, true);

        return $value;
    }

    protected function auditGateway(Transaction $transaction, Gateway $gateway, $oldBalance, $newBalance): void
    {
        $oldBalance = new Number($oldBalance);
        $finalAmount = $oldBalance->minus($newBalance)->toFloat();

        $gatewayLog = $this->gatewayLogManager->createLog(
            $finalAmount < 0 ? GatewayInterface::OPERATION_ADD : GatewayInterface::OPERATION_SUB,
            abs($finalAmount),
            $oldBalance,
            $transaction->getNumber(),
            $transaction->getCurrency(),
            $gateway,
            $gateway->getPaymentOptionEntity(),
            [
                'identifier' => $transaction->getId(),
                'reference_class' => get_class($transaction),
                'type' => $transaction->getType(),
            ]
        );

        $this->gatewayLogManager->save($gatewayLog);
    }

    protected function getType($type, $inverse = false)
    {
        if (!$inverse) {
            $types = [
                'deposit' => Transaction::TRANSACTION_TYPE_DEPOSIT,
                'withdraw' => Transaction::TRANSACTION_TYPE_WITHDRAW,
                'transfer' => Transaction::TRANSACTION_TYPE_TRANSFER,
                'bonus' => Transaction::TRANSACTION_TYPE_BONUS,
                'p2p_transfer' => Transaction::TRANSACTION_TYPE_P2P_TRANSFER,
                'commission' => Transaction::TRANSACTION_TYPE_COMMISSION,
                'revenue_share' => Transaction::TRANSACTION_TYPE_REVENUE_SHARE,
                'adjustment' => Transaction::TRANSACTION_TYPE_ADJUSTMENT,
                'debit_adjustment' => Transaction::TRANSACTION_TYPE_DEBIT_ADJUSTMENT,
                'credit_adjustment' => Transaction::TRANSACTION_TYPE_CREDIT_ADJUSTMENT,
            ];
        } else {
            $types = [
                Transaction::TRANSACTION_TYPE_DEPOSIT => 'deposit',
                Transaction::TRANSACTION_TYPE_WITHDRAW => 'withdraw',
                Transaction::TRANSACTION_TYPE_TRANSFER => 'transfer',
                Transaction::TRANSACTION_TYPE_BONUS => 'bonus',
                Transaction::TRANSACTION_TYPE_P2P_TRANSFER => 'p2p_transfer',
                Transaction::TRANSACTION_TYPE_COMMISSION => 'commission',
                Transaction::TRANSACTION_TYPE_REVENUE_SHARE => 'revenue_share',
                Transaction::TRANSACTION_TYPE_ADJUSTMENT => 'adjustment',
                Transaction::TRANSACTION_TYPE_DEBIT_ADJUSTMENT => 'debit_adjustment',
                Transaction::TRANSACTION_TYPE_CREDIT_ADJUSTMENT => 'credit_adjustment',
            ];
        }

        return array_get($types, $type);
    }
}