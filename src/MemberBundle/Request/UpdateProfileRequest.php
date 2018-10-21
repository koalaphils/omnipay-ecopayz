<?php

namespace MemberBundle\Request;

class UpdateProfileRequest
{
    private $customer;
    private $user;
    private $username;
    private $email;
    private $status;
    private $fullName;
    private $birthDate;
    private $country;
    private $groups;
    private $referrer;
    private $currency;
    private $joinedAt;

    private $affiliateLink;
    private $referrerSite;
    private $registrationSite;
    private $promoCode;

    private $riskSetting;
    private $tags;
    private $clientIp;

    private function __construct() {
        $this->groups = [];
    }

    public static function fromEntity(\DbBundle\Entity\Customer $customer): UpdateProfileRequest
    {
        $request = new UpdateProfileRequest();
        $request->customer = $customer;
        $request->user = $customer->getUser();
        $request->username = $customer->getUser()->getUsername();
        $request->email = $customer->getUser()->getEmail();
        $request->status = $customer->getUser()->isActive();
        $request->fullName = $customer->getFullName();
        $request->birthDate = $customer->getBirthDate();
        $request->country = $customer->getCountry()->getId();
        $request->currency = $customer->getCurrency()->getId();
        $request->referrer = $customer->getReferral();
        $request->joinedAt = $customer->getJoinedAt();
        $request->affiliateLink = $customer->getUser()->getPreference('affiliateCode');
        $request->referrerSite = $customer->getUser()->getPreference('referrer');
        $request->registrationSite = $customer->getUser()->getPreference('originUrl');
        $request->promoCode = $customer->getUser()->getPreference('promoCode', '');
        $request->riskSetting = $customer->getRiskSetting();
        $request->tags = $customer->getTags();
        $request->clientIp = $customer->getUser()->getPreference('ipAddress');

        foreach ($customer->getGroups() as $group) {
            $request->groups[] = $group->getId();
        }

        if ($customer->getAffiliate() !== null) {
            $request->referrer = $customer->getAffiliate()->getId();
        }

        return $request;
    }

    public function getCustomer(): \DbBundle\Entity\Customer
    {
        return $this->customer;
    }

    public function getUser(): \DbBundle\Entity\User
    {
        return $this->user;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getBirthDate(): \DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeInterface $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    public function getCountry(): int
    {
        return $this->country;
    }

    public function setCountry(int $country): void
    {
        $this->country = $country;
    }

    public function getCurrency(): int
    {
        return $this->currency;
    }

    public function setCurrency(int $currency): void
    {
        $this->currency = $currency;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    public function getReferrer(): ?int
    {
        return $this->referrer;
    }

    public function setReferrer(?int $referrer): void
    {
        $this->referrer = $referrer;
    }

    public function getJoinedAt(): \DateTimeInterface
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeInterface $joinedAt): void
    {
        $this->joinedAt = $joinedAt;
    }

    public function getAffiliateLink(): ?string
    {
        return $this->affiliateLink;
    }

    public function setAffiliateLink(?string $affiliateLink): void
    {
        $this->affiliateLink = $affiliateLink;
    }

    public function getReferrerSite(): ?string
    {
        if (is_null($this->referrerSite)) {
            return '';
        } else {
            return $this->referrerSite;
        }
    }

    public function setReferrerSite(?string $referrerSite): ?string
    {
        if (is_null($referrerSite)) {
            return '';
        } else {
            return $this->referrerSite = $referrerSite;
        }
    }

    public function getRegistrationSite(): ?string
    {
        if (is_null($this->registrationSite)) {
            return '';
        } else {
            return $this->registrationSite;
        }
    }

    public function setRegistrationSite(?string $registrationSite): ?string
    {
        if (is_null($registrationSite)) {
            return '';
        } else {
            return $this->registrationSite = $registrationSite;
        }
    }

    public function getPromoCode(): ?string
    {
        return $this->promoCode;
    }

    public function setPromoCode(?string $promoCode): ?string
    {
        return $this->promoCode = $promoCode;
    }

    public function setRiskSetting(?string $riskSetting): void
    {
        $this->riskSetting = $riskSetting;
    }

    public function getRiskSetting(): ?string
    {
        return $this->riskSetting;
    }

    public function setTags(?array $tags): void
    {
        $this->tags = $tags;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function getClientIp(): ?string
    {
        return $this->clientIp;
    }
}