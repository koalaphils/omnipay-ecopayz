<?php

namespace PaymentBundle\Controller\Bitcoin;

use Codeception\Test\Unit;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\User;
use DbBundle\Repository\CustomerRepository as MemberRepository;
use DbBundle\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use PaymentBundle\Component\Blockchain\Explorer;
use PaymentBundle\Component\Blockchain\Model\BlockchainTransaction;
use PaymentBundle\Component\Blockchain\Model\BlockchainTransactionInput;
use PaymentBundle\Component\Blockchain\Model\BlockchainTransactionOutput;
use PaymentBundle\Service\Blockchain;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayInterface;
use Payum\Core\Payum;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Payum\Core\Security\TokenInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use UnitTester;

class NotifyActionTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testExecuteNotSupportedRequest()
    {
        $this->expectException(RequestNotSupportedException::class);

        $notifyAction = new NotifyAction($this->make(Payum::class), $this->makeEmpty(TransactionRepository::class), $this->makeEmpty(MemberRepository::class));
        $notifyAction->setGateway($this->makeEmpty(GatewayInterface::class));

        $notifyAction->execute($this->make(Notify::class, [
            'token' => $this->makeEmpty(TokenInterface::class),
        ]));
    }

    public function testExecuteInvalidRequest()
    {
        $this->expectException(HttpResponse::class);
        $logger = new LoggerMock();

        try {
            $notifyAction = new NotifyAction($this->make(Payum::class), $this->makeEmpty(TransactionRepository::class), $this->makeEmpty(MemberRepository::class));
            $notifyAction->setGateway($this->makeEmpty(GatewayInterface::class));
            $notifyAction->setLogger($logger);

            $notifyAction->execute($this->make(Notify::class, [
                'token' => $this->makeEmpty(TokenInterface::class, [
                    'getGatewayName' => function () {
                        return 'bitcoin';
                    }
                ]),
            ]));
        } catch (HttpResponse $httpResponse) {
            $this->assertSame(LogLevel::ERROR, $logger->getLogLevel());
            $this->assertSame('Invalid Request', $logger->getMessage());
            $this->assertSame('*ok*', $httpResponse->getContent());

            throw $httpResponse;
        }
    }

    public function testExecuteMemberNotFound()
    {
        $this->expectException(HttpResponse::class);
        $logger = new LoggerMock();
        try {
            $notifyAction = new NotifyAction(
                $this->make(Payum::class),
                $this->makeEmpty(TransactionRepository::class),
                $this->makeEmpty(MemberRepository::class, ['getById' => function () {
                    throw new NoResultException();
                }])
            );
            $notifyAction->setGateway(new GatewayMock(
                '68f7b9257d72ca197d9ac7330663e36f4fe2b9858abeadaf97470d6d1c2f22c1',
                477563300,
                '16HcgcaeADNGxs1jHUcAxR2SAanaQE4Sjv',
                1
            ));
            $notifyAction->setLogger($logger);
            $notifyAction->execute($this->make(Notify::class, [
                'token' => $this->makeEmpty(TokenInterface::class, [
                    'getDetails' => function () {
                        return ['memberId' => 1];
                    },
                    'getGatewayName' => function () {
                        return 'bitcoin';
                    }
                ]),
            ]));

        } catch (HttpResponse $httpResponse) {
            $this->assertSame(LogLevel::CRITICAL, $logger->getLogLevel());
            $this->assertSame('Member not found', $logger->getMessage());
            $this->assertSame('*ok*', $httpResponse->getContent());

            throw $httpResponse;
        }
    }

    public function testExecuteNonUniqueMember()
    {
        $this->expectException(HttpResponse::class);
        $logger = new LoggerMock();
        try {
            $notifyAction = new NotifyAction(
                $this->make(Payum::class),
                $this->makeEmpty(TransactionRepository::class),
                $this->makeEmpty(MemberRepository::class, ['getById' => function () {
                    throw new NonUniqueResultException();
                }])
            );
            $notifyAction->setGateway(new GatewayMock(
                '68f7b9257d72ca197d9ac7330663e36f4fe2b9858abeadaf97470d6d1c2f22c1',
                477563300,
                '16HcgcaeADNGxs1jHUcAxR2SAanaQE4Sjv',
                1
            ));
            $notifyAction->setLogger($logger);
            $notifyAction->execute($this->make(Notify::class, [
                'token' => $this->makeEmpty(TokenInterface::class, [
                    'getDetails' => function () {
                        return ['memberId' => 1];
                    },
                    'getGatewayName' => function () {
                        return 'bitcoin';
                    }
                ]),
            ]));

        } catch (HttpResponse $httpResponse) {
            $this->assertSame(LogLevel::CRITICAL, $logger->getLogLevel());
            $this->assertSame('Multiple memebers has been found', $logger->getMessage());
            $this->assertSame('*ok*', $httpResponse->getContent());

            throw $httpResponse;
        }
    }

    public function testExecuteTransacitonNotFound()
    {
        $this->expectException(HttpResponse::class);
        $logger = new LoggerMock();
        try {
            $notifyAction = new NotifyAction(
                $this->make(Payum::class),
                $this->makeEmpty(TransactionRepository::class, [
                    'getRequestedBitcoinTransactionForMember' => function () {
                        throw new NoResultException();
                    }
                ]),
                $this->makeEmpty(MemberRepository::class, ['getById' => function () {
                    return $this->make(Member::class, ['id' => 1]);
                }])
            );
            $notifyAction->setGateway(new GatewayMock(
                '68f7b9257d72ca197d9ac7330663e36f4fe2b9858abeadaf97470d6d1c2f22c1',
                477563300,
                '16HcgcaeADNGxs1jHUcAxR2SAanaQE4Sjv',
                1
            ));
            $notifyAction->setLogger($logger);
            $notifyAction->execute($this->make(Notify::class, [
                'token' => $this->makeEmpty(TokenInterface::class, [
                    'getDetails' => function () {
                        return ['memberId' => 1];
                    },
                    'getGatewayName' => function () {
                        return 'bitcoin';
                    }
                ]),
            ]));

        } catch (HttpResponse $httpResponse) {
            $this->assertSame(LogLevel::ERROR, $logger->getLogLevel());
            $this->assertSame('Transaction Not found', $logger->getMessage());
            $this->assertSame('*ok*', $httpResponse->getContent());

            throw $httpResponse;
        }
    }

    public function testExecuteNonUniqueTransaction()
    {
        $this->expectException(HttpResponse::class);
        $logger = new LoggerMock();
        try {
            $notifyAction = new NotifyAction(
                $this->make(Payum::class),
                $this->makeEmpty(TransactionRepository::class, [
                    'getRequestedBitcoinTransactionForMember' => function () {
                        throw new NonUniqueResultException();
                    }
                ]),
                $this->makeEmpty(MemberRepository::class, ['getById' => function () {
                    return $this->make(Member::class, ['id' => 1]);
                }])
            );
            $notifyAction->setGateway(new GatewayMock(
                '68f7b9257d72ca197d9ac7330663e36f4fe2b9858abeadaf97470d6d1c2f22c1',
                477563300,
                '16HcgcaeADNGxs1jHUcAxR2SAanaQE4Sjv',
                1
            ));
            $notifyAction->setLogger($logger);
            $notifyAction->execute($this->make(Notify::class, [
                'token' => $this->makeEmpty(TokenInterface::class, [
                    'getDetails' => function () {
                        return ['memberId' => 1];
                    },
                    'getGatewayName' => function () {
                        return 'bitcoin';
                    }
                ]),
            ]));

        } catch (HttpResponse $httpResponse) {
            $this->assertSame(LogLevel::ERROR, $logger->getLogLevel());
            $this->assertSame('Multiple transaction found', $logger->getMessage());
            $this->assertSame('*ok*', $httpResponse->getContent());

            throw $httpResponse;
        }
    }

    public function testExecuteWrongTransactionHash()
    {
        $this->expectException(HttpResponse::class);
        $logger = new LoggerMock();
        try {
            $notifyAction = new NotifyAction(
                $this->make(Payum::class),
                $this->makeEmpty(TransactionRepository::class, [
                    'getRequestedBitcoinTransactionForMember' => function () {
                        return $this->make(Transaction::class, [
                            'getBitcoinTransactionHash' => '78f7b9257d72ca197d9ac7330663e36f4fe2b9858abeadaf97470d6d1c2f22c2'
                        ]);
                    }
                ]),
                $this->makeEmpty(MemberRepository::class, ['getById' => function () {
                    return $this->make(Member::class, ['id' => 1]);
                }])
            );
            $notifyAction->setGateway(new GatewayMock(
                '68f7b9257d72ca197d9ac7330663e36f4fe2b9858abeadaf97470d6d1c2f22c1',
                477563300,
                '16HcgcaeADNGxs1jHUcAxR2SAanaQE4Sjv',
                1
            ));
            $notifyAction->setLogger($logger);
            $notifyAction->execute($this->make(Notify::class, [
                'token' => $this->makeEmpty(TokenInterface::class, [
                    'getDetails' => function () {
                        return ['memberId' => 1];
                    },
                    'getGatewayName' => function () {
                        return 'bitcoin';
                    }
                ]),
            ]));

        } catch (HttpResponse $httpResponse) {
            $this->assertSame(LogLevel::ERROR, $logger->getLogLevel());
            $this->assertSame('Transaction hash expecting 78f7b9257d72ca197d9ac7330663e36f4fe2b9858abeadaf97470d6d1c2f22c2, but 68f7b9257d72ca197d9ac7330663e36f4fe2b9858abeadaf97470d6d1c2f22c1 was given', $logger->getMessage());
            $this->assertSame('*ok*', $httpResponse->getContent());

            throw $httpResponse;
        }
    }

    public function testExecuteWrontAddress()
    {
        $this->expectException(HttpResponse::class);
        $logger = new LoggerMock();
        try {
            $member = $this->make(Member::class, [
                'getId' => function () { return 1; },
                'getBitcoinAddress' => function () { return '26HcgcaeADNGxs1jHUcAxR2SAanaQE4Sj1'; },
                'getUser' => function () {
                    return $this->make(User::class);
                }
            ]);
            $notifyAction = new NotifyAction(
                $this->make(Payum::class),
                $this->makeEmpty(TransactionRepository::class, [
                    'getRequestedBitcoinTransactionForMember' => function () use ($member) {
                        return $this->make(Transaction::class, [
                            'getCustomer' => $member,
                            'getBitcoinTransactionHash' => '68f7b9257d72ca197d9ac7330663e36f4fe2b9858abeadaf97470d6d1c2f22c1',
                        ]);
                    }
                ]),
                $this->makeEmpty(MemberRepository::class, ['getById' => $member])
            );
            $notifyAction->setGateway(new GatewayMock(
                '68f7b9257d72ca197d9ac7330663e36f4fe2b9858abeadaf97470d6d1c2f22c1',
                477563300,
                '16HcgcaeADNGxs1jHUcAxR2SAanaQE4Sjv',
                1
            ));
            $notifyAction->setLogger($logger);
            $notifyAction->setTokenStorage($this->makeEmpty(TokenStorageInterface::class));
            $notifyAction->execute($this->make(Notify::class, [
                'token' => $this->makeEmpty(TokenInterface::class, [
                    'getDetails' => function () {
                        return ['memberId' => 1];
                    },
                    'getGatewayName' => function () {
                        return 'bitcoin';
                    }
                ]),
            ]));

        } catch (HttpResponse $httpResponse) {
            $this->assertSame(LogLevel::ERROR, $logger->getLogLevel());
            $this->assertSame('Expecting 26HcgcaeADNGxs1jHUcAxR2SAanaQE4Sj1 address, 16HcgcaeADNGxs1jHUcAxR2SAanaQE4Sjv address given', $logger->getMessage());
            $this->assertSame('*ok*', $httpResponse->getContent());

            throw $httpResponse;
        }
    }

    public function testExecuteNotYetConfirmed()
    {
        $this->expectException(HttpResponse::class);
        $logger = new LoggerMock();
        try {
            $member = $this->make(Member::class, [
                'getId' => function () { return 1; },
                'getBitcoinAddress' => function () { return '16HcgcaeADNGxs1jHUcAxR2SAanaQE4Sjv'; },
                'getUser' => function () {
                    return $this->make(User::class);
                }
            ]);
            $notifyAction = new NotifyAction(
                $this->make(Payum::class),
                $this->makeEmpty(TransactionRepository::class, [
                    'getEntityManager' => $this->makeEmpty(EntityManagerInterface::class),
                    'getRequestedBitcoinTransactionForMember' => function () use ($member) {
                        return $this->make(Transaction::class, [
                            'getCustomer' => $member,
                            'getBitcoinTransactionHash' => '68f7b9257d72ca197d9ac7330663e36f4fe2b9858abeadaf97470d6d1c2f22c1',
                        ]);
                    }
                ]),
                $this->makeEmpty(MemberRepository::class, ['getById' => $member])
            );
            $notifyAction->setGateway(new GatewayMock(
                '68f7b9257d72ca197d9ac7330663e36f4fe2b9858abeadaf97470d6d1c2f22c1',
                477563300,
                '16HcgcaeADNGxs1jHUcAxR2SAanaQE4Sjv',
                1
            ));
            $notifyAction->setLogger($logger);
            $notifyAction->setBlockchain($this->makeEmpty(Blockchain::class, [
                'getExplorer' => $this->makeEmpty(Explorer::class, [
                    'getTransaction' => $this->makeEmpty(BlockchainTransaction::class, [
                        'findInputWithAddress' => $this->makeEmpty(BlockchainTransactionInput::class, ['getPreviousOutN' => 1]),
                        'findOutputWithN' => $this->makeEmpty(BlockchainTransactionOutput::class, ['getAddress' => '26HcgcaeADNGxs1jHUcAxR2SAanaQE4Sj1']),
                    ])
                ]),
            ]));
            $notifyAction->setTokenStorage($this->makeEmpty(TokenStorageInterface::class));
            $notifyAction->execute($this->make(Notify::class, [
                'token' => $this->makeEmpty(TokenInterface::class, [
                    'getDetails' => function () {
                        return ['memberId' => 1];
                    },
                    'getGatewayName' => function () {
                        return 'bitcoin';
                    }
                ]),
            ]));

        } catch (HttpResponse $httpResponse) {
            $this->assertSame(LogLevel::INFO, $logger->getLogLevel());
            $this->assertSame('Need 3 or more confirmation to finish the callback', $logger->getMessage());
            $this->assertSame('', $httpResponse->getContent());

            throw $httpResponse;
        }
    }

    public function testExecuteConfirmed()
    {
        $this->expectException(HttpResponse::class);
        $logger = new LoggerMock();
        try {
            $member = $this->make(Member::class, [
                'getId' => function () { return 1; },
                'getBitcoinAddress' => function () { return '16HcgcaeADNGxs1jHUcAxR2SAanaQE4Sjv'; },
                'getUser' => function () {
                    return $this->make(User::class);
                }
            ]);
            $notifyAction = new NotifyAction(
                $this->make(Payum::class),
                $this->makeEmpty(TransactionRepository::class, [
                    'getEntityManager' => $this->makeEmpty(EntityManagerInterface::class),
                    'getRequestedBitcoinTransactionForMember' => function () use ($member) {
                        return $this->make(Transaction::class, [
                            'getCustomer' => $member,
                            'getBitcoinTransactionHash' => '68f7b9257d72ca197d9ac7330663e36f4fe2b9858abeadaf97470d6d1c2f22c1',
                        ]);
                    }
                ]),
                $this->makeEmpty(MemberRepository::class, ['getById' => $member])
            );
            $notifyAction->setGateway(new GatewayMock(
                '68f7b9257d72ca197d9ac7330663e36f4fe2b9858abeadaf97470d6d1c2f22c1',
                477563300,
                '16HcgcaeADNGxs1jHUcAxR2SAanaQE4Sjv',
                3
            ));
            $notifyAction->setLogger($logger);
            $notifyAction->setBlockchain($this->makeEmpty(Blockchain::class, [
                'getExplorer' => $this->makeEmpty(Explorer::class, [
                    'getTransaction' => $this->makeEmpty(BlockchainTransaction::class, [
                        'findInputWithAddress' => $this->makeEmpty(BlockchainTransactionInput::class, ['getPreviousOutN' => 1]),
                        'findOutputWithN' => $this->makeEmpty(BlockchainTransactionOutput::class, ['getAddress' => '26HcgcaeADNGxs1jHUcAxR2SAanaQE4Sj1']),
                    ])
                ]),
            ]));
            $notifyAction->setTokenStorage($this->makeEmpty(TokenStorageInterface::class));
            $notifyAction->execute($this->make(Notify::class, [
                'token' => $this->makeEmpty(TokenInterface::class, [
                    'getDetails' => function () {
                        return ['memberId' => 1];
                    },
                    'getGatewayName' => function () {
                        return 'bitcoin';
                    }
                ]),
            ]));

        } catch (HttpResponse $httpResponse) {
            $this->assertSame(LogLevel::INFO, $logger->getLogLevel());
            $this->assertSame('Callback done, from 3 confirmation', $logger->getMessage());
            $this->assertSame('*ok*', $httpResponse->getContent());

            throw $httpResponse;
        }
    }
}


class LoggerMock extends AbstractLogger implements LoggerInterface
{
    private $log = [];

    public function log($level, $message, array $context = []): void
    {
        $this->log = ['level' => $level, 'message' => $message, 'context' => $context];
    }

    public function getLogLevel(): string
    {
        return $this->log['level'];
    }

    public function getMessage(): string
    {
        return $this->log['message'];
    }

    public function getContext(): array
    {
        return $this->log['context'];
    }
}

class GatewayMock implements GatewayInterface
{
    private $transactionHash;
    private $value;
    private $address;
    private $confirmation;

    public function execute($request, $catchReply = false)
    {
        if ($request instanceof GetHttpRequest) {
            $request->query = [
                'transaction_hash' => $this->transactionHash,
                'value' => $this->value,
                'address' => $this->address,
                'confirmations' => $this->confirmation,
            ];
        }
    }

    public function __construct(string $transactionHash, int $value, string $address, string $confirmation)
    {
        $this->transactionHash = $transactionHash;
        $this->value = $value;
        $this->address = $address;
        $this->confirmation = $confirmation;
    }
}
