<?php

namespace PromoBundle\Manager;

use AppBundle\Manager\AbstractManager;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Exceptions\FormValidationException;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\Promo;
use DbBundle\Repository\PromoRepository;
use Doctrine\ORM\Query;

class PromoManager extends AbstractManager
{
    public function getActivePromos($filters = null): array
    {
        $filters['status'] = true;
        $results = $this->getRepository()->getPromoList($filters, Query::HYDRATE_ARRAY);

        return $results;
    }

    protected function getRepository()
    {
        return $this->getDoctrine()->getRepository('DbBundle:Promo');
    }

    public function validatePersonalLinkConditions(Member $member): array
    {
        $promo = $this->getPromoRepository()->findByCode(['search' => Promo::PROMO_REFERAFRIEND, 'status' => true], Query::HYDRATE_OBJECT);
        $countryCode = $member->getCountry();
        $currencyCode = $member->getCurrency()->getCode();

        if ($promo) {
            $exempted = array_values(array_filter($promo->getDetail('conditions'), function ($var) use ($countryCode) {
                return in_array($countryCode, $var['exempted']);
            }));

            if (!$exempted && !$member->getIsAffiliate()) {
                $promoDetails = array_values(array_filter($promo->getDetail('conditions'), function ($var) use ($countryCode, $currencyCode) {
                    return in_array($countryCode, $var['countries']) && in_array($currencyCode, $var['currencies']);
                }));

                if (!$promoDetails) {
                    $promoDetails = array_values(array_filter($promo->getDetail('conditions'), function ($var) {
                        return $var['isDefault'] === true;
                    }));
                }
                
                return $promoDetails;
            }
        }

        return [];
    }

    public function getPersonalLink(Member $member): string
    {
        $personalLinkId = $member->getPersonalLink();
        $promoDetails = $this->validatePersonalLinkConditions($member);

        if ($promoDetails && $personalLinkId) {
            $url = $url = $promoDetails[0]['url'];
            $fullUrl = $url."?rid=".$personalLinkId;

            if ($promoDetails[0]['isLinkShortened']) {
                return $this->generateShortenedLink($fullUrl);
            } else {
                return $fullUrl;
            }
        }

        return '';
    }

    public function generateShortenedLink(string $url) {
        $link = @file_get_contents ('https://urlz.fr/api_new.php?url='.urlencode ($url));

        if ($link && $link != 'Erreur') {
            return $link;
        };

        return $url;
    }

    public function createPersonalLinkId(Member $member): void
    {
        $isValid = (bool) $this->validatePersonalLinkConditions($member);

        if ($isValid) {
            $member->setPersonalLink();
            $this->save($member);
        } else {
            $member->removePersonalLink();
            $this->save($member);
        }
    }

    private function getPromoRepository(): PromoRepository
    {
        return $this->getEntityManager()->getRepository(Promo::class);
    }
}
