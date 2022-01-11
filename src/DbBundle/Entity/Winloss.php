<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\Product;
use DbBundle\Entity\CommissionPeriod;

class Winloss extends Entity
{
    use Traits\ActionTrait;
    use Traits\TimestampTrait;

    private $date;
    private $member;
    private $affiliate;
    private $payout;
    private $period;
    private $pinUserCode;
    private $product;
    private $status;
    private $turnover;
    private $pregeneratedAt;

    public function __construct()
    {

    }

    public function getMember(): ?Member
    {
        return $this->member;
    }

    public function getAffiliate(): ?int
    {
        return $this->affiliate;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getPayout(): string
    {
        return $this->payout;
    }

    public function getTurnover(): string
    {
        return $this->turnover;
    }

    public function getPeriod(): ?CommissionPeriod
    {
        return $this->period;
    }

    public function getPinUserCode(): string
    {
        return $this->pinUserCode;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getPregeneratedAt()
    {
        return $this->pregeneratedAt;
    }


}