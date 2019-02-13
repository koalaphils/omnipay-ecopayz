<?php
namespace TransactionBundle\Manager;

use DbBundle\Entity\Transaction;

class TransactionManagerTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    /**
     *
     * @dataProvider transactionNumberSearchDataProvider
     */
    public function testIsTransactionNumber($expectedResult, $searchString)
    {
        $transactionManager = new TransactionManager();
        $result = $transactionManager->isTransactionNumber($searchString);

        $this->assertSame($expectedResult, $result);
    }

    /**
     *
     * @dataProvider transactionTypeDataProvider
     */
    public function testIsTransactionType($expectedResult, $type, bool $inverse)
    {
        $transactionManager = new TransactionManager();
        $result = $transactionManager->getType($type, $inverse);

        $this->assertSame($expectedResult, $result);
    }

    public function transactionNumberSearchDataProvider() : array
    {
        return [
                [true ,'20180616-174911-3'],
                [true ,'20180616-174911-33'],
                [true ,'20180616-174911-333'],
                [true ,'20180511-194752-6-10648-6637'],
                [true, '20180620-123333-8-134594'],
                [false ,'Laurent Roiseau'],
                [false ,'AC88000AA2'],
                [false ,'AsianOdds'],
                [false ,'SKRILL'],
                [false ,'user.name@local.com'],
                [false ,'username@local.com']
        ];
    }

    public function transactionTypeDataProvider() : array
    {
        return [
            [Transaction::TRANSACTION_TYPE_DEPOSIT, 'deposit', false],
            [Transaction::TRANSACTION_TYPE_WITHDRAW, 'withdraw', false],
            [Transaction::TRANSACTION_TYPE_TRANSFER, 'transfer', false],
            [Transaction::TRANSACTION_TYPE_BONUS, 'bonus', false],
            [Transaction::TRANSACTION_TYPE_P2P_TRANSFER, 'p2p_transfer', false],
            [Transaction::TRANSACTION_TYPE_DWL, 'dwl', false],
            [Transaction::TRANSACTION_TYPE_BET, 'bet', false],
            [Transaction::TRANSACTION_TYPE_COMMISSION, 'commission', false],
            [null, 'deposi', false],
            [null, 'withdra', false],
            ['deposit', Transaction::TRANSACTION_TYPE_DEPOSIT, true],
            ['withdraw', Transaction::TRANSACTION_TYPE_WITHDRAW, true],
            ['transfer', Transaction::TRANSACTION_TYPE_TRANSFER, true],
            ['bonus', Transaction::TRANSACTION_TYPE_BONUS, true],
            ['p2p_transfer', Transaction::TRANSACTION_TYPE_P2P_TRANSFER, true],
            ['dwl', Transaction::TRANSACTION_TYPE_DWL, true],
            ['bet', Transaction::TRANSACTION_TYPE_BET, true],
            ['commission', Transaction::TRANSACTION_TYPE_COMMISSION, true],
            [null, 10, true],
            [null, 11, true],
        ];
    }
}