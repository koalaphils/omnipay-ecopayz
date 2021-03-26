<?php

namespace DbBundle\Entity;

class Report extends Entity
{
    const REPORT_PRODUCT_TYPE = 'product';
    const REPORT_CUSTOMER_TYPE = 'customer';
    const REPORT_PAYMENTGATEWAY_TYPE = 'paymentGateway';
    const REPORT_AFTER_TRANSACTION = 'after';
    const REPORT_BEFORE_TRANSACTION = 'before';
    const REPORT_RANGE_TRANSACTION = 'in_range';
}
