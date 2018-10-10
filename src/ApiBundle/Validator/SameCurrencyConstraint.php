<?php

namespace ApiBundle\Validator;

use Symfony\Component\Validator\Constraint;

class SameCurrencyConstraint extends Constraint
{
    public $message = 'Peer to peer transaction is applicable between same currency transaction request. Please check your request or contact customer support for assistance.';
}
