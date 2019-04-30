<?php

namespace MemberBundle\Request;

use DbBundle\Entity\Customer;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

class CreateMemberRequest implements GroupSequenceProviderInterface
{
    private $username;
    private $email;
    private $password;
    private $confirmPassword;
    private $status;
    private $fullName;
    private $birthDate;
    private $country;
    private $groups;
    private $referal;
    private $currency;
    private $joinedAt;
    private $gender;
    private $phoneNumber;
    private $useEmail;

    public function __construct()
    {
        $this->username = '';
        $this->email = '';
        $this->fullName = '';
        $this->status = true;
        $this->groups = [];
        $this->password = '';
        $this->confirmPassword = '';
        $this->gender = Customer::MEMBER_GENDER_NOT_SET;
        $this->phoneNumber = '';
        $this->useEmail = true;
    }

    public function isUseEmail(): bool
    {
        return $this->useEmail;
    }

    public function setUseEmail(bool $useEmail): void
    {
        $this->useEmail = $useEmail;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        if ($phoneNumber === null) {
            $this->phoneNumber = '';
        } else {
            $this->phoneNumber = $phoneNumber;
        }
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

    public function setEmail(?string $email): void
    {
        if ($email === null) {
            $this->email = '';
        } else {
            $this->email = $email;
        }
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getConfirmPassword(): string
    {
        return $this->confirmPassword;
    }

    public function setConfirmPassword(string $confirmPassword): void
    {
        $this->confirmPassword = $confirmPassword;
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): void
    {
        if ($fullName === null) {
            $this->fullName = '';
        } else {
            $this->fullName = $fullName;
        }
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthdate(?\DateTimeInterface $birthdate): void
    {
        $this->birthDate = $birthdate;
    }

    public function getCountry(): ?int
    {
        return $this->country;
    }

    public function setCountry(?int $country): void
    {
        $this->country = $country;
    }

    public function getGender(): int
    {
        return $this->gender;
    }

    public function setGender(int $gender): void
    {
        $this->gender = $gender;
    }

    public function getCurrency(): ?int
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

    public function getReferal(): ?int
    {
        return $this->referal;
    }

    public function setReferal(?int $referal): void
    {
        $this->referal = $referal;
    }

    public function getJoinedAt(): ?\DateTimeInterface
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeInterface $joinedAt): void
    {
        $this->joinedAt = $joinedAt;
    }

    public function isConfirmPasswordCorrect(): bool
    {
        return $this->password === $this->confirmPassword;
    }

    public function getGroupSequence()
    {
        if ($this->isUseEmail()) {
            return [['CreateMemberRequest', 'withEmail']];
        } else {
            return [['CreateMemberRequest', 'withPhone']];
        }
    }
}
