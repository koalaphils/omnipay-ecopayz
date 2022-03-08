<?php

namespace TransactionBundle\Validator\Constraints;

use DbBundle\Entity\User;
use DbBundle\Entity\Promo;
use DbBundle\Repository\TransactionRepository as TransactionRepository;
use DbBundle\Repository\MemberPromoRepository;
use DbBundle\Repository\PromoRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ReferralConstraintValidator extends ConstraintValidator
{
    private $transactionRepository;
    private $memberPromoRepository;
    private $promoRepository;

    public function __construct(TransactionRepository $transactionRepository, MemberPromoRepository $memberPromoRepository, PromoRepository $promoRepository)
    {
        $this->transactionRepository = $transactionRepository;
        $this->memberPromoRepository = $memberPromoRepository;
        $this->promoRepository = $promoRepository;
    }

    public function validate($transaction, Constraint $constraint)
    {
        if ($transaction->getTypeText() == 'bonus' && !$transaction->isEnd()) {
            $refTransaction = $this->transactionRepository->findByReferenceNumber($transaction->getReferralsTransaction(), null, true);

            if (!$transaction->getPromo()) {
                $this->context->buildViolation('Please select promo code.')
                        ->atPath('promo')->addViolation();
            }

            if (!$refTransaction) {
                $promo = $this->promoRepository->findById($transaction->getPromo());
                if ($promo) {
                    if ($promo->getCode() == Promo::PROMO_REFERAFRIEND) {
                        $this->context->buildViolation('Please add the correct Transaction Number.')
                        ->atPath('details[referrals][transaction]')->addViolation();
                    }
                }
            } else {
                if (!$refTransaction->isDeposit()) {
                    $this->context->buildViolation('REFERAFRIEND bonus must be added on a deposit transaction.')
                    ->atPath('details[referrals][transaction]')->addViolation();
                }

                $filters = [
                    'referrer' => $transaction->getCustomer()->getIdentifier(),
                    'member' => $refTransaction->getCustomer()->getIdentifier(),
                    'promo' => $transaction->getPromo()
                ];

                $memberPromo = $this->memberPromoRepository->findReferralMemberPromo($filters);
                if ($memberPromo) {
                    if ($memberPromo->getTransaction() !== null) {
                        if ($memberPromo->getTransaction()->isEnd()) {
                            $this->context->buildViolation('A bonus already exists for the referred member. One bonus per referral only.')
                            ->atPath('details[referrals][transaction]')->addViolation();
                        }
                    } else {
                        if ($memberPromo->getReferrer()->getIdentifier() !== $transaction->getCustomer()->getIdentifier()) {
                            $this->context->buildViolation('Please add the bonus to the correct referrer.')
                            ->atPath('details[referrals][transaction]')->addViolation();
                        }
                    }
                } else {
                    $this->context->buildViolation('Please add the correct Transaction Number.')
                    ->atPath('details[referrals][transaction]')->addViolation();
                }
            }
        }
    }
}