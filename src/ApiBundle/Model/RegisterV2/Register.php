<?php

namespace ApiBundle\Model\RegisterV2;

use Doctrine\Common\Collections\ArrayCollection;

class Register
{
    private $email;
    private $fullName;
    private $birthDate;
    private $contacts;
    private $country;
    private $socials;
    private $currency;
    private $depositMethod;
    private $bookies;
    private $affiliate;
    private $tag;
    private $websiteUrl;
    private $preferredReferralName;
    private $preferredPaymentGateway;

    public function __construct()
    {
        $this->setContacts([]);
        $this->setSocials([]);
        $this->setAffiliate([]);
        $this->bookies = new ArrayCollection();
    }

    public function setEmail($email): self
    {
        $this->email = trim($email);

        return $this;
    }

    public function getEmail():? string
    {
        return $this->email;
    }

    public function setFullName($fullName): self
    {
        $this->fullName = trim($fullName);

        return $this;
    }

    public function getFullName():? string
    {
        return $this->fullName;
    }

    public function setBirthDate($birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getBirthDate():? \DateTime
    {
        return $this->birthDate;
    }

    public function setContacts($contacts): self
    {
        $this->contacts = $contacts;

        return $this;
    }

    public function getContacts():? array
    {
        return $this->contacts;
    }

    public function setCountry($country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getCountry():? \DbBundle\Entity\Country
    {
        return $this->country;
    }

    public function setSocials($socials): self
    {
        $this->socials = $socials;

        return $this;
    }

    public function getSocials():? array
    {
        return $this->socials;
    }

    public function setCurrency($currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency():? \DbBundle\Entity\Currency
    {
        return $this->currency;
    }

    public function setDepositMethod($depositMethod): self
    {
        $this->depositMethod = $depositMethod;

        return $this;
    }

    public function getDepositMethod():? \DbBundle\Entity\PaymentOption
    {
        return $this->depositMethod;
    }

    public function setBookies($bookies): self
    {
        $this->bookies = $bookies;

        return $this;
    }

    public function getBookies(): ArrayCollection
    {
        return $this->bookies;
    }


    public function setAffiliate($affiliate):? self
    {
        $this->affiliate = $affiliate;

        return $this;
    }

    public function getAffiliate():? array
    {
        return $this->affiliate;
    }

    public function getContactDetail($key): string
    {
        $contacts = $this->getContacts();

        return isset($contacts[$key]) ? trim($contacts[$key]) : '';
    }

    public function getSocialDetail($key): string
    {
        $socials = $this->getSocials();

        return isset($socials[$key]) ? trim($socials[$key]) : '';
    }

    public function getAffiliateDetail($key): string
    {
        $affiliate = $this->getAffiliate();

        return isset($affiliate[$key]) ? trim($affiliate[$key]) : '';
    }

    public function hasDepositMethod(): bool
    {
        return $this->getDepositMethod() ? true : false;
    }

    public function getBankDetails(): array
    {
        return [
            'name' => $this->getDepositMethod()->getCode(),
            'account' => $this->getEmail(),
            'holder' => $this->getFullName(),
        ];
    }

    public function hasBookies(): bool
    {
        return $this->getBookies()->count() > 0 ? true : false;
    }

    public function getUsername(): string
    {
        return uniqid(str_replace(' ', '', $this->getFullName()) . '_');
    }

    public function getContactDetails(): array
    {
        return [
            [
                'type' => 'mobile',
                'value' =>  $this->getContactDetail('mobile'),
            ],
        ];
    }

    public function getSocialDetails(): array
    {
        return [
            [
                'type' => 'skype',
                'value' => $this->getSocialDetail('skype'),
            ],
        ];
    }

    public function getRoles(): array
    {
        return [
            'ROLE_MEMBER' => 2,
        ];
    }

    public function getAffiliateDetails(): array
    {
        return [
            'name' => $this->getAffiliateDetail('code'),
            'code' => $this->getAffiliateDetail('promo'),
        ];
    }

    public function setTag(?string $tag): ?self
    {
        $this->tag = $tag;

        return $this;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setWebsiteUrl(?string $websiteUrl): ?self
    {
        $this->websiteUrl = $websiteUrl;

        return $this;
    }

    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    public function setPreferredReferralName(?string $preferredReferralName): ?self
    {
        $this->preferredReferralName = $preferredReferralName;

        return $this;
    }

    public function getPreferredReferralName(): ?string
    {
        return $this->preferredReferralName;
    }

    public function setPreferredPaymentGateway(?string $preferredPaymentGateway): ?self
    {
        $this->preferredPaymentGateway = $preferredPaymentGateway;

        return $this;
    }

    public function getPreferredPaymentGateway(): ?string
    {
        return $this->preferredPaymentGateway;
    }
}