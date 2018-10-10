<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace CommissionBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Description of ValidCommissionPeriod
 *
 * @author cydrick
 */
class ValidCommissionPeriod extends Constraint
{
    protected $message = 'Invalid Commission Period';
    protected $messageIncludeInOtherCommission = 'The date for commission period was already included to other commission period.';
    protected $messageIncorrectStartDate = 'The start date is not correct.';

    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT];
    }
    
    public function getMessage(): string
    {
        return $this->message;
    }
    
    public function getMessageIncludeInOtherCommission(): string
    {
        return $this->messageIncludeInOtherCommission;
    }
    
    public function getMessageIncorrectStartDate(): string
    {
        return $this->messageIncorrectStartDate;
    }
}
