<?php

declare(strict_types = 1);

namespace ApiBundle\Request\Transaction;

use DbBundle\Entity\Customer as Member;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

class TransferRequest
{   
    protected $member;
    protected $paymentOptionType;
    protected $from;
    protected $to = [];
    
    public function __construct(Member $member, Request $request)
    {
        $this->member = $member;
        $this->paymentOptionType = $request->get('payment_option_type', '');
        
        if (!empty($request->get('from', null))) { 
            $this->from = $request->get('from');
        }

        foreach ($request->get('to', []) as $item) {
            $this->to[] = new TransactionItemRequest(
                $item['id'] ?? null, 
                $item['amount'] ??  ''
            );

            // $this->to[] = new Product(
            //     $product['username'] ?? '',
            //     $product['product_code'] ?? '',
            //     (string) $product['amount'] ?? '',
            //     $product['meta'] ?? [],
            //     $this->paymentOptionType
            // );
        }
    }

    public function getPaymentOptionType(): string
    {
        return $this->paymentOptionType;
    }

    public function getFrom(): ?int
    {
        return $this->from;
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function getMemberId(): int
    {
        return (int) $this->member->getId();
    }
}