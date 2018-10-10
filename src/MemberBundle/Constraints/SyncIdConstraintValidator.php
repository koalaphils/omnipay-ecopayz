<?php

namespace MemberBundle\Constraints;

use CustomerBundle\Manager\CustomerProductManager;
use Doctrine\ORM\NonUniqueResultException;
use MemberBundle\Request\CreateMemberProductRequest;
use MemberBundle\Request\UpdateMemberProductRequest;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SyncIdConstraintValidator extends ConstraintValidator
{
    private $manager;

    public function __construct(CustomerProductManager $manager)
    {
        $this->manager = $manager;
    }

    public function validate($entity, Constraint $constraint)
    {
        $syncId = $entity->getBrokerage();
        
        if (empty($syncId)) {
            return;
        }

        if ($entity instanceof CreateMemberProductRequest) {
            $customerProductId = '';
        } elseif ($entity instanceof UpdateMemberProductRequest) {
            $customerProductId = $entity->getCustomerProduct();
        }
        try {
            $canSync = $this->manager->canSyncToCustomerProduct($syncId, $customerProductId);
            if (!$canSync) {
                $this->context->buildViolation($constraint->message)
                    ->atPath('brokerage')
                    ->addViolation()
                ;
            }
        } catch (NonUniqueResultException $e) {
            $this->context->buildViolation($constraint->syncIdUsedMoreThanOneMessage)
                ->atPath('brokerage')
                ->addViolation()
            ;
        }
    }
}
