<?php

declare(strict_types = 1);

namespace ApiBundle\Request\CurrentMember;

use Symfony\Component\HttpFoundation\Request;

class GetTransactionListRequest
{
    /**
     * @var string
     */
    private $search;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var array
     */
    private $orders;

    /**
     * @var int
     */
    private $page;

    /**
     * @var \DateTimeImmutable
     */
    private $fromDate;

    /**
     * @var \DateTimeImmutable
     */
    private $toDate;

    /**
     * @var int
     */
    private $interval;

    /**
     * @var string[]
     */
    private $transactionTypes;

    /**
     * @var string[]
     */
    private $transactionStatuses;

    /**
     * @var string[]
     */
    private $paymentOptions;

    public static function createFromRequest(Request $request): self
    {
        $instance = new static();
        $instance->search = $request->get('search');
        $instance->orders = $request->get('orders');
        $instance->limit = $request->get('limit');
        $instance->page = $request->get('page');
        $instance->fromDate = $request->get('from_date');
        $instance->toDate = $request->get('to_date');
        $instance->interval = $request->get('interval');
        $instance->transactionTypes = $request->get('types');
        $instance->transactionStatuses = $request->get('statuses');
        $instance->paymentOptions = $request->get('payment_options');

        return $instance;
    }
}