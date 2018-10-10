<?php

namespace MemberBundle\Constraints;

use AppBundle\Manager\SettingManager;
use DbBundle\Entity\MemberReferralName;
use DbBundle\Repository\MemberReferralNameRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MaxActiveReferralNameConstraintValidator extends ConstraintValidator
{
    private $doctrine;
    private $settingManager;

    public function __construct(Registry $doctrine, SettingManager $settingManager)
    {
        $this->doctrine = $doctrine;
        $this->settingManager = $settingManager;
    }

    public function validate($value, Constraint $constraint)
    {
        $expLang = new ExpressionLanguage();
        $memberId = $expLang->evaluate($constraint->getMemberIdPath(), ['object' => $value]);
        $totalActive = $this->getMemberReferralNameRepository()->getReferralNameActiveCount($memberId);

        if ($totalActive >= $this->getMaxReferralName()) {
            $this->context->buildViolation($constraint->getMessage())
                ->atPath($constraint->getErrorPath())
                ->setParameter('{{ max }}', $this->getMaxReferralName())
                ->addViolation();
        }
    }

    private function getMemberReferralNameRepository(): MemberReferralNameRepository
    {
        return $this->doctrine->getRepository(MemberReferralName::class);
    }

    private function getMaxReferralName(): int
    {
        return $this->settingManager->getSetting('member.referralName.max');
    }
}