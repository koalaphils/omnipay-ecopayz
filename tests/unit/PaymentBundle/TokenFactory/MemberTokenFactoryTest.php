<?php

namespace PaymentBundle\TokenFactory;

use Codeception\Test\Unit;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UnitTester;
use DbBundle\Entity\Customer as Member;

class MemberTokenFactoryTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testCreateToken()
    {
        $urlGenerator = new MockUrlGenerator();
        $memberTokenFactory = new MemberTokenFactory($urlGenerator);
        $member = $this->tester->make(Member::class, ['id' => '1']);

        $token = $memberTokenFactory->createToken('testgateway', $member, 'testpath');

        $this->assertInstanceOf(\PaymentBundle\Model\MemberToken::class, $token);
        $this->assertSame('MQ==', $token->getHash());
        $this->assertSame('http://mock.test/MQ==', $token->getTargetUrl());
    }
}

final class MockUrlGenerator implements UrlGeneratorInterface
{
    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH): string
    {
        return "http://mock.test/" . $parameters['hash'];
    }

    public function getContext(): \Symfony\Component\Routing\RequestContext
    {
    }

    public function setContext(\Symfony\Component\Routing\RequestContext $context)
    {
    }
}
