<?php

namespace CommissionBundle\Service;

use AppBundle\Manager\SettingManager;
use CommissionBundle\Request\CommissionSettingRequest;
use DateTime;
use DateTimeZone;
use DateTimeImmutable;
use DateTimeInterface;
use DbBundle\Entity\CommissionPeriod;
use DbBundle\Entity\MemberRunningCommission;
use DbBundle\Repository\CommissionPeriodRepository;
use DbBundle\Repository\MemberRunningCommissionRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use JMS\JobQueueBundle\Entity\Job;
use JMS\JobQueueBundle\Entity\Repository\JobRepository;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of CommissionService
 *
 * @author cydrick
 */
class CommissionService
{
    use ContainerAwareTrait;

    const SCHEDULER_FREQUENCY_MONTHLY = 'monthly';
    const SCHEDULER_FREQUENCY_DAILY = 'daily';
    const SCHEDULER_FREQUENCY_WEEKLY = 'weekly';
    const SCHEDULER_FREQUENCY_YEARLY = 'yearly';

    private $validator;

    public function handleCommissionSettingRequest(CommissionSettingRequest $request): Response
    {
        $settingManager = $this->getSettingManager();
        $conditions = [];
        foreach ($request->getConditions() as $condition) {
            $conditions[] = $condition;
        }

        $settingManager->saveSetting('commission', [
            'startDate' => $request->getStartDate()->format('Y-m-d'),
            'enable' => $request->isEnable(),
            'period' => [
                'frequency' => $request->getFrequency(),
                'every' => $request->getEvery(),
                'day' => $request->getDay(),
            ],
            'payout' => [
                'days' => $request->getPayoutDay(),
                'time' => $request->getTime(),
            ],
            'conditions' => $conditions,
        ]);

        $this->createOrUpdateCommissionPeriod($request->getDateOfRequest());

        return Response::create('', Response::HTTP_ACCEPTED);
    }

    public function createOrUpdateCommissionPeriod(DateTimeInterface $dateFor): void
    {
        try {
            $this->getEntityManager()->beginTransaction();
            $commissionSetting = $this->getSettingManager()->getSetting('commission');
            $lastPeriod = $this->getCommissionPeriodRepository()->getCommissionPeriodBeforeOrOnTheDate($dateFor);
            if (!($lastPeriod instanceof CommissionPeriod)) {
                $lastPeriod = new CommissionPeriod();
                $lastPeriod->setDWLDateFrom(DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $commissionSetting['startDate'] . ' 00:00:00'));
                $this->computeRangeOfCommissionSchedule($lastPeriod);
                $this->validateCommissionPeriod($lastPeriod);
                $this->getCommissionPeriodRepository()->save($lastPeriod);
            } elseif ($lastPeriod->getDWLDateFrom() <= $dateFor && $lastPeriod->getDWLDateTo() >= $dateFor) {
                $this->computeRangeOfCommissionSchedule($lastPeriod);
                $this->validateCommissionPeriod($lastPeriod);
                $this->getCommissionPeriodRepository()->save($lastPeriod);
            }
            $this->createOrUpdateJobForPeriod($lastPeriod);

            $periods = $this->getCommissionPeriodRepository()->getCommissionPeriodsAfterTheDate($lastPeriod->getDWLDateTo());

            foreach ($periods as $period) {
                $period->setDWLDateFrom($lastPeriod->getDWLDateTo()->modify('+1 day'));
                if ($period->getDWLDateFrom() > $dateFor) {
                    $this->getMemberRunningCommissionRepository()->removeMemberRunningCommissionForPeriod($period);
                    $this->getCommissionPeriodRepository()->remove($period);
                } else {
                    $this->computeRangeOfCommissionSchedule($period);
                    $this->validateCommissionPeriod($period);
                    $this->getCommissionPeriodRepository()->save($period);
                    $lastPeriod = $period;
                    $this->createOrUpdateJobForPeriod($lastPeriod);
                }
            }

            while ($lastPeriod->getDWLDateTo() < $dateFor) {
                $nextPeriod = new CommissionPeriod();
                $nextPeriod->setDWLDateFrom($lastPeriod->getDWLDateTo()->modify('+1 day'));
                $this->computeRangeOfCommissionSchedule($nextPeriod);
                $this->validateCommissionPeriod($nextPeriod);
                $this->getCommissionPeriodRepository()->save($nextPeriod);
                $lastPeriod = $nextPeriod;
                $this->createOrUpdateJobForPeriod($lastPeriod);
            }
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }
    }

    private function createOrUpdateJobForPeriod(CommissionPeriod $commissionPeriod): void
    {
        $computeJob = $this
            ->getJobRepository()
            ->findJobForRelatedEntity('revenueshare:period:compute', $commissionPeriod, [Job::STATE_PENDING]);
        if (!($computeJob instanceof Job)) {
            $computeJob = new Job(
                'revenueshare:period:compute',
                [$this->getUser()->getUsername(), '--period', $commissionPeriod->getId(), '--env', $this->getEnvironment()],
                true,
                'payout'
            );
        }
        $computeJob->setExecuteAfter(new DateTime($commissionPeriod->getPayoutAt()->modify('-1 day')->format(DateTime::ATOM)));
        $computeJob->addRelatedEntity($commissionPeriod);

        $this->getEntityManager()->persist($computeJob);
        $this->getEntityManager()->flush($computeJob);

        if ($this->getMemberRunningCommissionRepository()->totalMemberRunningCommissionForCommissionPeriod($commissionPeriod) > 0) {
            $this->runComputeForPeriod($commissionPeriod);
        }
    }

    private function runComputeForPeriod(CommissionPeriod $commissionPeriod)
    {
        $computeJob = new Job(
            'revenueshare:period:compute',
            [$this->getUser()->getUsername(), '--period', $commissionPeriod->getId(), '--env', $this->getEnvironment()],
            true,
            'payout'
        );
        $computeJob->addRelatedEntity($commissionPeriod);
        $this->getEntityManager()->persist($computeJob);
        $this->getEntityManager()->flush($computeJob);
    }

    private function computeRangeOfCommissionSchedule(CommissionPeriod $commissionPeriod): void
    {
        $commissionSetting = $this->getSettingManager()->getSetting('commission');
        $scheduleEvery = $commissionSetting['period']['every'];
        $scheduleFrequency = $commissionSetting['period']['frequency'];
        $scheduleDay = $commissionSetting['period']['day'];
        $scheduleTime = $commissionSetting['payout']['time'];
        $schedulePayout = $commissionSetting['payout']['days'];

        if ($scheduleFrequency === self::SCHEDULER_FREQUENCY_MONTHLY) {
            /* @var $dwlDateTo DateTimeImmutable */
            $month = (int) $commissionPeriod->getDWLDateFrom()->format('m');
            $month += (int) $scheduleEvery;
            $year = (int) $commissionPeriod->getDWLDateFrom()->format('Y');
            $day = $scheduleDay;
            if ($month > 12) {
                $month -= 12;
                $year += 1;
            }

            switch ($month) {
                case 4: case 6: case 9: case 11:
                    $day = ($scheduleDay === 31) ? 30 : $scheduleDay;
                    break;
                case 2:
                    if ($scheduleDay > 29 && ($year%4) === 0) {
                        $day = 29;
                    } elseif ($scheduleDay > 28) {
                        $day = 28;
                    }
                    break;
            }

            if ($scheduleDay === 'last') {
                $dwlDateTo = DateTimeImmutable::createFromFormat('Y-m-t H:i:s', sprintf('%s-%s-01 00:00:00', $year, $month));
            } else {
                $dwlDateTo = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', sprintf('%s-%s-%s 00:00:00', $year, $month, $day));
            }
        } elseif ($scheduleFrequency === self::SCHEDULER_FREQUENCY_WEEKLY) {
            $weekname = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
            $dwlDateTo = $commissionPeriod->getDWLDateFrom();
            for ($i = 0; $i < $scheduleEvery; $i++) {
                $dwlDateTo = $dwlDateTo->modify(sprintf('next %s', $weekname[$scheduleDay - 1]));
            }
        } elseif ($scheduleFrequency === self::SCHEDULER_FREQUENCY_DAILY) {
            $dwlDateTo = $commissionPeriod->getDWLDateFrom()->modify(sprintf('+%s %s', $scheduleEvery, ($scheduleEvery > 1) ? 'days': 'day'));
        }

        $commissionPeriod->setDWLDateTo($dwlDateTo->modify('-1 day'));
        $commissionPeriod->setConditions($commissionSetting['conditions']);

        $payoutDate = $commissionPeriod->getDWLDateTo()->modify('+' . $schedulePayout . ' days');
        $commissionPeriod->setPayoutAt($payoutDate->createFromFormat('Y-m-d H:i', $payoutDate->format('Y-m-d ' . $scheduleTime)));
    }

    public function setValidator(\Symfony\Component\Validator\Validator\ValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }

    private function validateCommissionPeriod(CommissionPeriod $commissionPeriod): void
    {
        $violationList = $this->validator->validate($commissionPeriod);
        if ($violationList->count() > 0) {
            $violations = [];
            foreach ($violationList as $violation) {
                $violations[] = $violation->getMessage();
            }
            throw new \RuntimeException(implode(", ", $violations));
        }
    }

    private function getCommissionPeriodRepository(): CommissionPeriodRepository
    {
        return $this->container->get('doctrine')->getRepository(CommissionPeriod::class);
    }

    private function getJobRepository(): JobRepository
    {
        return $this->getDoctrine()->getRepository(Job::class);
    }

    private function getEntityManager(): EntityManager
    {
        return $this->getDoctrine()->getManager();
    }

    private function getDoctrine(): Registry
    {
        return $this->container->get('doctrine');
    }

    private function getSettingManager(): SettingManager
    {
        return $this->container->get('app.setting_manager');
    }

    private function getMemberRunningCommissionRepository(): MemberRunningCommissionRepository
    {
        return $this->getDoctrine()->getRepository(MemberRunningCommission::class);
    }

    private function getUser()
    {
        if (!$this->container->has('security.token_storage')) {
            throw new LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            return;
        }

        return $user;
    }

    private function getEnvironment(): string
    {
        return $this->container->get('kernel')->getEnvironment();
    }
}
