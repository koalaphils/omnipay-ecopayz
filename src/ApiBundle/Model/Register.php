<?php

namespace ApiBundle\Model;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\HttpFoundation\Request;
use DbBundle\Entity\Customer;

/**
 * Description of Register
 *
 * @author Paolo Abendanio <cpabendanio@zmtsys.com>
 */
class Register
{
    const DATE_FORMAT = 'Y-m-d';

    private $email;
    private $firstName;
    private $middleInitial;
    private $lastName;
    private $birthDate;
    private $contact;
    private $country;
    private $socials;
    private $currency;
    private $depositMethod;
    private $bookies;
    private $banks;
    private $affiliate;
    private $promo;
    private $phoneNumber;
    private $password;
    private $pinUserCode;
    private $pinLoginId;
    private $countryPhoneCode;

    public function __construct()
    {
        $this->setSocials([]);
        $this->setBookies([]);
        $this->setBanks([]);
        $this->phoneNumber = $this->email = $this->firstName = $this->middleInitial = $this->lastName = $this->birthDate = $this->contact = $this->country = $this->currency = $this->affiliate = $this->promo = '';
    }

    public function setPromo($promo): self
    {
        $this->promo = $promo;

        return $this;
    }

    public function getPromo()
    {
        return $this->promo;
    }

    public function setAffiliate($affiliate): self
    {
        $this->affiliate = $affiliate;

        return $this;
    }

    public function getAffiliate()
    {
        return $this->affiliate;
    }

    public function setEmail($email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setPhoneNumber($phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function setCountryPhoneCode($countryPhoneCode): self
    {
        $this->countryPhoneCode = $countryPhoneCode;

        return $this;
    }

    public function getCountryPhoneCode()
    {
        return $this->countryPhoneCode;
    }

    public function setPinUserCode($userCode): self
    {
        $this->pinUserCode = $userCode;

        return $this;
    }

    public function getPinUserCode()
    {
        return $this->pinUserCode;
    }

    public function setPinLoginId($loginId): self
    {
        $this->pinLoginId = $loginId;

        return $this;
    }

    public function getPinLoginId()
    {
        return $this->pinLoginId;
    }

    public function setFirstName($fName): self
    {
        $this->firstName = $fName;

        return $this;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setPassword($password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setMiddleInitial($mName): self
    {
        $this->middleInitial = $mName;

        return $this;
    }

    public function getMiddleInitial()
    {
        return $this->middleInitial;
    }

    public function setLastName($lName): self
    {
        $this->lastName = $lName;

        return $this;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setContact($contact): self
    {
        $this->contact = $contact;

        return $this;
    }

    public function getContact()
    {
        return $this->contact;
    }

    public function setCountry($country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getSocials(): \Doctrine\Common\Collections\ArrayCollection
    {
        return $this->socials;
    }

    public function setSocials($socials): self
    {
        if ($socials instanceof \Doctrine\Common\Collections\ArrayCollection) {
            $this->socials = $socials;
        } else {
            $this->socials = new \Doctrine\Common\Collections\ArrayCollection($socials);
        }

        return $this;
    }

    public function setBirthDate($birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    /**
     * Get birth date in string format
     * @return string
     */
    public function getBirthDateString()
    {
        if ($this->birthDate) {
            return $this->birthDate->format(self::DATE_FORMAT);
        }

        return '';
    }

    public function setCurrency($currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function setDepositMethod($depositMethod): self
    {
        $this->depositMethod = $depositMethod;

        return $this;
    }

    public function getDepositMethod()
    {
        return $this->depositMethod;
    }

    public function getBookies(): \Doctrine\Common\Collections\ArrayCollection
    {
        return $this->bookies;
    }

    public function setBookies($bookies): self
    {
        if ($bookies instanceof \Doctrine\Common\Collections\ArrayCollection) {
            $this->bookies = $bookies;
        } else {
            $this->bookies = new \Doctrine\Common\Collections\ArrayCollection($bookies);
        }

        return $this;
    }

    public function getBanks(): \Doctrine\Common\Collections\ArrayCollection
    {
        return $this->banks;
    }

    public function setBanks($banks): self
    {
        if ($banks instanceof \Doctrine\Common\Collections\ArrayCollection) {
            $this->banks = $banks;
        } else {
            $this->banks = new \Doctrine\Common\Collections\ArrayCollection($banks);
        }

        return $this;
    }

    public function validateEmail(ExecutionContextInterface $context, $payload)
    {
        if ($this->getEmail()) {
            $context->buildViolation('Email Address already in use')
                ->atPath('email')
                ->addViolation();
        }
    }
}
