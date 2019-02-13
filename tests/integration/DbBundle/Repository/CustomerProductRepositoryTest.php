<?php

namespace DbBundle\Repository;

use DbBundle\Entity\Currency;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\Product;


class CustomerProductRepositoryTest extends \Codeception\Test\Unit
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

    public function testGetTotalActiveMemberProductByReferrer_referralsHaveMultipleProduct()
    {

        $memberIdWithReferralsWithMultipleProductsNotOnlyACWallet = $this->generateMemberWithReferralsWithMultipleProductsNotOnlyACWallet();
        $memberRepository = $this->getModule('Symfony2')->grabService('doctrine')->getRepository(MemberProduct::class);
        $referralCountAndCustomerProductCount = $memberRepository->getTotalActiveMemberProductByReferrer($memberIdWithReferralsWithMultipleProductsNotOnlyACWallet);

        $this->assertInstanceOf(Member::class, $memberIdWithReferralsWithMultipleProductsNotOnlyACWallet);
        $this->assertSame(2, (int)$referralCountAndCustomerProductCount['totalCustomers']);
        $this->assertSame(2, (int)$referralCountAndCustomerProductCount['totalCustomerProducts']);
    }

    // TODO: move DB tests into an integration suite (for testing external systems like databases,etc)
    public function testGetTotalActiveMemberProductByReferrer_referralsHaveNoProduct()
    {

        $memberWithOnlyACWalletProduct = $this->generateMemberWithReferralThatHasOnlyACWalletAsProduct();
        $memberRepository = $this->getModule('Symfony2')->grabService('doctrine')->getRepository(MemberProduct::class);
        $referralCountAndCustomerProductCount = $memberRepository->getTotalActiveMemberProductByReferrer($memberWithOnlyACWalletProduct);

        $this->assertSame(0, (int)$referralCountAndCustomerProductCount['totalCustomerProducts']);
        // todo: this is a BUG, even though referral has no product aside from AC wallet, he should still be counted as a referral
        $this->assertSame(0, (int)$referralCountAndCustomerProductCount['totalCustomers']);


    }

    private function generateMemberWithReferralThatHasOnlyACWalletAsProduct(): Member
    {
        $em = $this->getModule('Doctrine2')->_getEntityManager();
        $euroCurrency = $em->getRepository(Currency::class)->findByCode('EUR');

        $referrer = $this->tester->have(Member::class, [
            'currency' => $euroCurrency,
        ]);
        $referralsWithOnlyACWalletAsProduct = $this->tester->have(Member::class, ['affiliate' => $referrer, 'currency' => $euroCurrency]);

        $this->tester->have(MemberProduct::class,
            [
                'product' => $em->getRepository(Product::class)->getAcWalletProduct(),
                'customer' => $referralsWithOnlyACWalletAsProduct,
            ]
        );

        return $referrer;
    }


    private function generateMemberWithReferralsWithMultipleProductsNotOnlyACWallet()
    {
        $em = $this->getModule('Doctrine2')->_getEntityManager();

        $euroCurrency = $em->getRepository(Currency::class)->findByCode('EUR');
        $referrer = $this->tester->have(Member::class, [
            'currency' => $euroCurrency
        ]);
        $referralsWithMultipleProduct = $this->tester->haveMultiple(Member::class, 2, [
            'affiliate' => $referrer,
            'currency' => $euroCurrency
        ]);

        foreach ($referralsWithMultipleProduct as $referral) {
            $this->tester->have(MemberProduct::class,
                [
                    'customer' => $referral,
                    'product' => $em->getRepository(Product::class)->getAcWalletProduct()
                ]
            );
            $this->tester->have(MemberProduct::class,
                [
                    'customer' => $referral,
                    'product' => $em->getRepository(Product::class)->findOneByName('AsianOdds')
                ]
            );
        }

        return $referrer;
    }
}