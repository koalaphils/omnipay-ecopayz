<?php

namespace ApiBundle\Manager;

use DateTime;
use AppBundle\Helper\ReferralToolGenerator;
use AppBundle\Manager\AbstractManager;
use DbBundle\Entity\BannerImage;
use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\MemberBanner;
use DbBundle\Entity\MemberRunningCommission;
use DbBundle\Repository\CommissionPeriodRepository;
use DbBundle\Repository\CustomerRepository as MemberRepository;
use DbBundle\Repository\MemberBannerRepository;
use DbBundle\Repository\MemberRunningCommissionRepository;
use Symfony\Component\HttpFoundation\Response;
use MemberBundle\Manager\MemberManager as MemberBundleManager;

class MemberManager extends AbstractManager
{
    public function getReferralLinkList(): array
    {
        $referralLinkOptions = $this->getMemberBannerRepository()->getMemberReferralLinkOptions($this->getUser()->getMemberId());

        $referralLinks = [];
        foreach ($referralLinkOptions as $referralLinkOption) {
            $referralLinks[] = $this->getReferralToolGenerator()
                ->generateReferralLink([
                    'type' => BannerImage::getTypeString($referralLinkOption['type']),
                    'language' => $referralLinkOption['language'],
                    'trackingCode' => $referralLinkOption['trackingCode'],
                ]);
        }

        return array_unique($referralLinks);
    }

    public function getCurrentPeriodReferralTurnoversAndCommissions(array $filters): array
    {
        return $this->getMemberBundleManager()->getCurrentPeriodReferralTurnoversAndCommissions(
            $this->getUser()->getMemberId(), new DateTime('now'), $filters
        );
    }

    public function getPreviousSuccessfulMemberRunningCommissions(int $periodCount): array
    {
        $memberRunningCommissions = array_column(
            $this->getMemberRunningCommissionRepository()
                ->getPreviousSuccessfulMemberRunningCommissions(
                    $this->getUser()->getMemberId(), 1, $periodCount
                ),
            null, 'commissionPeriodId'
        );

        $commissionPeriods = array_column(
            $this->getCommissionPeriodRepository()
            ->getSuccessfulPayoutOrComputationCommissionPeriods(1, $periodCount),
            null, 'commissionPeriodId'
        );

        array_walk($commissionPeriods, function(&$result, $key) use ($memberRunningCommissions) {
            $result = array_merge($result, [
                'runningCommission' => 0,
            ]);

            if (array_has($memberRunningCommissions, $key)) {
                $result = array_get($memberRunningCommissions, $key);
            }

            return $result;
        });

        return $commissionPeriods;
    }

    public function getLastSuccessfulMemberRunningCommission(): string
    {
        return $this
            ->getMemberRunningCommissionRepository()
            ->totalRunningCommissionOfMember(
                $this->getUser()->getMemberId()
            );
    }

    public function confirmReferrerTermsAndConditions(int $hasConfirm)
    {
        $member = $this->getUser()->getMember();

        try {
            if ($hasConfirm == true) {
                $member->confirmReferrerTermsAndConditions();
            } else {
                $member->unconfirmReferrerTermsAndConditions();
            }

            $this->save($member);
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), $e->getCode()];
        }

        return ['member' => $member, 'code' => Response::HTTP_OK];
    }

    protected function getRepository(): MemberRepository
    {
        return $this->getDoctrine()->getRepository(Member::class);
    }

    private function getMemberBannerRepository(): MemberBannerRepository
    {
        return $this->getDoctrine()->getRepository(MemberBanner::class);
    }

    private function getMemberRunningCommissionRepository(): MemberRunningCommissionRepository
    {
        return $this->getDoctrine()->getRepository(MemberRunningCommission::class);
    }

    private function getCommissionPeriodRepository(): CommissionPeriodRepository
    {
        return $this->getDoctrine()->getRepository(CommissionPeriod::class);
    }

    private function getReferralToolGenerator(): ReferralToolGenerator
    {
        return $this->get('app.referral_tool_generator');
    }

    private function getMemberBundleManager(): MemberBundleManager
    {
        return $this->get('member.manager');
    }
}