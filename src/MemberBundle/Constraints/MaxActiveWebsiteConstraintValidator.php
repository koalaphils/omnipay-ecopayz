<?php

namespace MemberBundle\Constraints;

use AppBundle\Manager\SettingManager;
use DbBundle\Entity\MemberWebsite;
use DbBundle\Repository\MemberWebsiteRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MaxActiveWebsiteConstraintValidator extends ConstraintValidator
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
        $totalActive = $this->getMemberWebsiteRepository()->getActiveCount($memberId);

        if ($totalActive >= $this->getMaxWebsite()) {
            $this->context->buildViolation($constraint->getMessage())
                ->atPath($constraint->getErrorPath())
                ->setParameter('{{ max }}', $this->getMaxWebsite())
                ->addViolation();
        }
    }

    private function getMemberWebsiteRepository(): MemberWebsiteRepository
    {
        return $this->doctrine->getRepository(MemberWebsite::class);
    }

    private function getMaxWebsite(): int
    {
        return $this->settingManager->getSetting('member.website.max');
    }
}
