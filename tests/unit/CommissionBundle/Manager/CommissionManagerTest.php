<?php

namespace CommissionBundle\Manager;

use AppBundle\Helper\NumberHelper;
use Codeception\Test\Unit;
use CommissionBundle\Manager\CommissionManager;
use CurrencyBundle\Manager\CurrencyManager;
use DateTime;
use DbBundle\Entity\CurrencyRate;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\DWL;
use DbBundle\Entity\SubTransaction;
use MemberBundle\Manager\MemberManager;
use UnitTester;

class CommissionManagerTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;
    
    /**
     * @dataProvider commissionInformationDataProvider
     */
    public function testSetCommissionInformationForTransaction(string $turnover, string $percentage, string $rate, array $expectedResult)
    {
        $referrer = $this->tester->make(Member::class);
        $subTransaction = $this->tester->make(SubTransaction::class, ['details' => ['dwl' => ['turnover' => $turnover]]]);
        $subTransaction->getCustomerProduct()->getCustomer()->setReferrer($referrer);
        $subTransaction->getParent()->setCurrency($subTransaction->getCustomerProduct()->getCustomer()->getCurrency());
        $subTransaction->getParent()->setCustomer($subTransaction->getCustomerProduct()->getCustomer());
        $subTransaction->getParent()->addSubTransaction($subTransaction);
        
        $currencyRate = $this->tester->make(CurrencyRate::class, [
            'sourceCurrency' => $subTransaction->getParent()->getCurrency(),
            'destinationCurrency' => $referrer->getCurrency(),
            'rate' => $rate,
            'destinationRate' => 1,
        ]);
        $dwl = $this->tester->make(DWL::class, [
            'product' => $subTransaction->getCustomerProduct()->getProduct(),
            'currency' => $subTransaction->getParent()->getCurrency(),
            'date' => new DateTime('2018-07-01'),
        ]);
        
        $currencyManager = $this->make(CurrencyManager::class, ['getConvertionRate' => function () use ($currencyRate) {
            return $currencyRate;
        }]);
        $memberManager = $this->make(MemberManager::class, [
            'getMemberCommissionForProductForDate' => function () use($percentage) {
                return $percentage;
            }
        ]);
        $commissionManager = $this->make(CommissionManager::class, ['currencyManager' => $currencyManager, 'memberManager' => $memberManager]);
        $commissionManager->setCommissionInformationForTransaction($subTransaction->getParent(), $dwl);
        
        $computedAmount = $subTransaction->getParent()->getComputedAmount();
        $this->assertSame(
            NumberHelper::toFloat($expectedResult['commission']),
            NumberHelper::toFloat($computedAmount[$subTransaction->getParent()->getCurrency()->getCode()])
        );

        $this->assertSame(
            NumberHelper::toFloat($expectedResult['converted']),
            NumberHelper::toFloat($computedAmount[$referrer->getCurrency()->getCode()])
        );
    }
    
    public function commissionInformationDataProvider()
    {
        yield [200, 10, 2, ['commission' => 20, 'converted' => 10]];
        yield [200, 10, 0.2, ['commission' => 20, 'converted' => 100]];
        yield [100, 4, 62.5, ['commission' => 4, 'converted' => 0.064]];
        yield [4720.75, 7.3, 0.72, ['commission' => 344.61475, 'converted' => 478.63159722223]];
        yield [8286.23, 0.35, 72.82, ['commission' => 29.001805, 'converted' => 0.39826702828894]];
        yield [4246.85, 11.31, 60.6, ['commission' => 480.318735, 'converted' => 7.9260517326735]];
    }
}
