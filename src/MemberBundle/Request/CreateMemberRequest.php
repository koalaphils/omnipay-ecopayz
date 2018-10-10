<?php

namespace MemberBundle\Request;

class CreateMemberRequest
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

    public function __construct()
    {
        $this->username = '';
        $this->email = '';
        $this->fullName = '';
        $this->status = true;
        $this->groups = [];
        $this->password = '';
        $this->confirmPassword = '';
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

    public function setFullName(string $fullName): void
    {
        $this->fullName = $fullName;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthdate(\DateTimeInterface $birthdate): void
    {
        $this->birthDate = $birthdate;
    }

    public function getCountry(): ?int
    {
        return $this->country;
    }

    public function setCountry(int $country): void
    {
        $this->country = $country;
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
}
