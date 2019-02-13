<?php

namespace BrokerageBundle\Service;

use AppBundle\Manager\SettingManager;
use AppBundle\ValueObject\Number;
use Codeception\Test\Unit;
use CommissionBundle\Manager\CommissionManager;
use DbBundle\Entity\AuditRevision;
use DbBundle\Entity\AuditRevisionLog;
use DbBundle\Entity\Currency;
use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\DWL;
use DbBundle\Entity\Product;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Repository\AuditRevisionRepository;
use DbBundle\Repository\CurrencyRepository;
use DbBundle\Repository\CustomerProductRepository;
use DbBundle\Repository\DWLRepository;
use DbBundle\Repository\SubTransactionRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use League\FactoryMuffin\Faker\Facade as Faker;
use TransactionBundle\Manager\TransactionManager;

class RecomputeDwlServiceTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @dataProvider recomputeDwlDataProvider
     */
    public function testComputeTotalAmountFromLog(array $auditLogs, array $brokerage, string $memberProductBalance, array $expectedResults): void
    {
        $memberProduct = $this->tester->make(MemberProduct::class, [
            'balance' => $memberProductBalance
        ]);
        $transaction = $this->tester->make(Transaction::class, [
            'customer' => $memberProduct->getCustomer(),
            'currency' => $memberProduct->getCustomer()->getCurrency(),
            'createdAt' => Faker::dateTime(),
        ]);
        $transaction->addSubTransaction($this->tester->make(SubTransaction::class, [
            'customerProduct' => $memberProduct,
        ]));
        $lastSubTranasctionAuditLog = null;
        $auditLogEntities = $this->generatAuditLogEntities($auditLogs, $lastSubTranasctionAuditLog);
        $recomputeDwlService = new RecomputeDwlService();
        $recomputeDwlService->setAuditRepository(
            $this->makeEmpty(AuditRevisionRepository::class, [
                'getLastAuditLogFor' => function () use ($lastSubTranasctionAuditLog) {
                    return $lastSubTranasctionAuditLog;
                },
                'getAuditLogsForDWLSubtransactionIdentifier' => function () use ($auditLogEntities) {
                    return new MockIrateResult($auditLogEntities);
                }
            ])
        );


        $totalAdded = $recomputeDwlService->computeTotalAmountFromLog($transaction->getFirstSubtransaction());
        $this->assertTrue($totalAdded->equals($expectedResults['computedAmount']));
    }

    /**
     * @dataProvider recomputeDwlDataProvider
     */
    public function testRecomputeDWLForMemberProduct(array $auditLogs, array $brokerage, string $memberProductBalance, array $expectedResults): void
    {
        $memberProduct = $this->tester->make(MemberProduct::class, [
            'id' => 1,
            'product' => $this->tester->make(Product::class, ['id' => 1]),
            'balance' => $memberProductBalance,
            'customer' => $this->tester->make(Customer::class, [
                'currency' => $this->tester->make(Currency::class, [
                    'id' => 1,
                    'code' => 'PHP',
                ]),
            ])
        ]);
        $transaction = $this->tester->make(Transaction::class, [
            'customer' => $memberProduct->getCustomer(),
            'currency' => $memberProduct->getCustomer()->getCurrency(),
            'createdAt' => Faker::dateTime(),
        ]);
        $transaction->addSubTransaction($this->tester->make(SubTransaction::class, [
            'customerProduct' => $memberProduct,
        ]));
        $lastSubTranasctionAuditLog = null;
        $auditLogEntities = $this->generatAuditLogEntities($auditLogs, $lastSubTranasctionAuditLog);
        $recomputeDwlService = new RecomputeDwlService();
        $recomputeDwlService->setAuditRepository(
            $this->makeEmpty(AuditRevisionRepository::class, [
                'getLastAuditLogFor' => function () use ($lastSubTranasctionAuditLog) {
                    return $lastSubTranasctionAuditLog;
                },
                'getAuditLogsForDWLSubtransactionIdentifier' => function () use ($auditLogEntities) {
                    return new MockIrateResult($auditLogEntities);
                }
            ])
        );
        $recomputeDwlService->setMemberProductRepository(
            $this->makeEmpty(CustomerProductRepository::class, [
                'getSyncedMemberProduct' => function () use ($memberProduct) {
                    return $memberProduct;
                }
            ])
        );
        $recomputeDwlService->setDWLRepository(
            $this->makeEmpty(DWLRepository::class, [
                'findDWLByDateProductAndCurrency' => function () use ($memberProduct) {
                    return $this->tester->make(DWL::class, [
                        'id' => 1,
                        'currency' => $memberProduct->getCustomer()->getCurrency()
                    ]);
                }
            ])
        );
        $recomputeDwlService->setSubTransactionRepository($this->makeEmpty(SubTransactionRepository::class, [
            'getSubTransactionByDwlAndMemberProduct' => function () use ($transaction) {
                return $transaction->getFirstSubtransaction();
            }
        ]));
        $recomputeDwlService->setCommissionManager($this->makeEmpty(CommissionManager::class));
        $recomputeDwlService->setTransactionManager($this->make(TransactionManager::class, [
            'processTransactionSummary' => function () {},
            'getCurrencyRepository' => function () use ($memberProduct) {
                return $this->make(CurrencyRepository::class, [
                    'find' => function () use ($memberProduct) {
                        return $memberProduct->getCurrency();
                    }
                ]);
            },
            '_getSettingManager' => function () {
                return $this->make(SettingManager::class, [
                    'getSetting' => function () {
                        return '';
                    }
                ]);
            }
        ]));
        $recomputeDwlService->setEntityManager($this->makeEmpty(EntityManager::class));
        $recomputeDwlService->recomputeDWLForMemberProduct(0, new \DateTimeImmutable(), $brokerage['win_loss'], $brokerage['stake'], $brokerage['turnover'], $brokerage['current_balance']);
        $this->assertTrue((new Number($expectedResults['memberProductBalance']))->equals($memberProduct->getBalance()));
        $this->assertTrue((new Number($expectedResults['dwlItemCurrentBalance']))->equals($transaction->getFirstSubtransaction()->getDwlCustomerBalance()));
        $this->assertTrue((new Number($expectedResults['newAmount']))->equals($transaction->getFirstSubtransaction()->getAmount()));
    }

    public function recomputeDwlDataProvider()
    {
        yield [
            'auditLogs' => [
                ['category' => AuditRevisionLog::CATEGORY_CUSTOMER_PRODUCT, 'balanceFrom' => '100', 'balanceTo' => '200'],
                ['category' => AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DWL, 'memberProductBalance' => '200'],
                ['category' => AuditRevisionLog::CATEGORY_CUSTOMER_PRODUCT, 'balanceFrom' => '200', 'balanceTo' => '300'],
                ['category' => AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DWL, 'memberProductBalance' => '300'],
                ['category' => AuditRevisionLog::CATEGORY_CUSTOMER_PRODUCT, 'balanceFrom' => '300', 'balanceTo' => '400'],
                ['category' => AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DWL, 'memberProductBalance' => '400'],
                ['category' => AuditRevisionLog::CATEGORY_CUSTOMER_PRODUCT, 'balanceFrom' => '400', 'balanceTo' => '650'],
                ['category' => AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DWL, 'memberProductBalance' => '650'],
            ],
            'brokerage' => [
                'win_loss' => '50',
                'turnover' => '100',
                'stake' => '50',
                'current_balance'
            ],
            'memberProductBalance' => '650',
            'expectedResults' => [
                'memberProductBalance' => '200',
                'dwlItemCurrentBalance' => '100',
                'newAmount' => '100',
                'computedAmount' => '550',
            ]
        ];
    }

    private function generatAuditLogEntities(array $auditLogs, ?AuditLog &$lastSubTranasctionAuditLog): array
    {
        $auditLogEntities = [];

        foreach ($auditLogs as $auditLog) {
            if ($auditLog['category'] === AuditRevisionLog::CATEGORY_CUSTOMER_PRODUCT) {
                $auditLogEntities[] = $this->tester->make(AuditRevisionLog::class, [
                    'category' => AuditRevisionLog::CATEGORY_CUSTOMER_PRODUCT,
                    'details' => [
                        'fields' => [
                            'balance' => [
                                $auditLog['balanceFrom'],
                                $auditLog['balanceTo'],
                            ]
                        ]
                    ],
                    'auditRevision' => $this->tester->make(AuditRevision::class, [
                        'timestamp' => Faker::dateTime(),
                    ]),
                ]);
            } else {
                $subtransactionLog = $this->tester->make(AuditRevisionLog::class, [
                    'category' => AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DWL,
                    'details' => [
                        'details' => ['customerProduct' => ['balance' => $auditLog['memberProductBalance']]]
                    ],
                    'auditRevision' => $this->tester->make(AuditRevision::class, [
                        'timestamp' => Faker::dateTime(),
                    ]),
                ]);
                $lastSubTranasctionAuditLog = $subtransactionLog;
                $auditLogEntities[] = $subtransactionLog;
            }
        }

        return $auditLogEntities;
    }
}


class MockIrateResult extends IterableResult
{
    private $data;
    private $currentIndex = -1;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function next()
    {
        $this->currentIndex++;

        return $this->data[$this->currentIndex] ?? false;
    }

    public function current()
    {
        return [$this->data[$this->currentIndex]];
    }
}