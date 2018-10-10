<?php

namespace MemberBundle\Constraints;

use Symfony\Component\Validator\Constraint;

class SyncIdConstraint extends Constraint
{
    public $message = 'This member is already linked to another member product.';
    public $syncIdUsedMoreThanOneMessage = 'This brokerage was linked to multiple product, Pls. check the other product with this Brokerage';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}

