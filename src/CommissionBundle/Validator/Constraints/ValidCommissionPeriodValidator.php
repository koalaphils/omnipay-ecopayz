<?php

namespace CommissionBundle\Validator\Constraints;

use DbBundle\Entity\CommissionPeriod;
use DbBundle\Repository\CommissionPeriodRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidCommissionPeriodValidator extends ConstraintValidator
{
    
    private $commissionPeriodRepository;
    private $commissionPeriodStartDate;
    
    /**
     * @param CommissionPeriod $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!($value instanceof CommissionPeriod) || !($constraint instanceof ValidCommissionPeriod)) {
            return;
        }
        
        // check if overlaps
        $commissionPeriod = $this->commissionPeriodRepository->getCommissionPeriodForDate($value->getDWLDateFrom());
        if ($commissionPeriod instanceof CommissionPeriod && $commissionPeriod->getId() !== $value->getId()) {
            $this->context->buildViolation($constraint->getMessageIncludeInOtherCommission())->addViolation();
            return;
        }
        
        $commissionPeriod = $this->commissionPeriodRepository->getCommissionPeriodForDate($value->getDWLDateTo());
        if ($commissionPeriod instanceof CommissionPeriod && $commissionPeriod->getId() !== $value->getId()) {
            $this->context->buildViolation($constraint->getMessageIncludeInOtherCommission())->addViolation();
            return;
        }
        
        $preceedingCommission = $this->commissionPeriodRepository->getPreceedingCommissionPeriod($value);
        $preceedingCommissionDateToExpected = $value->getDWLDateFrom()->modify('-1 day');
        if ($preceedingCommission instanceof CommissionPeriod
            && $preceedingCommission->getDWLDateTo()->format('Y-m-d') !== $preceedingCommissionDateToExpected->format('Y-m-d')
        ) {
            $this->context->buildViolation($constraint->getMessageIncorrectStartDate())->addViolation();
            return;
        } elseif (!($preceedingCommission instanceof CommissionPeriod)
            && $value->getDWLDateFrom()->format('Y-m-d') !== $this->commissionPeriodStartDate
        ) {
            $this->context->buildViolation($constraint->getMessageIncorrectStartDate())->addViolation();
            return;
        }
        
    }
    
    public function __construct(CommissionPeriodRepository $commissionPeriodRepository, string $commissionPeriodStartDate)
    {
        $this->commissionPeriodRepository = $commissionPeriodRepository;
        $this->commissionPeriodStartDate = $commissionPeriodStartDate;
    }
}
