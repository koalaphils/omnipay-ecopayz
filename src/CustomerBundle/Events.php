<?php

namespace CustomerBundle;

final class Events
{
    const RISK_SETTING_SAVE = 'bo.event.risk_setting_save';
    const EVENT_CUSTOMER_CREATED = 'bo.event.customer_created';
    const EVENT_CUSTOMER_PRODUCT_SAVE = 'bo.event.customer_product_save';
    const EVENT_CUSTOMER_PRODUCT_ACTIVATED = 'bo.event.customer_product_activated';
    const EVENT_CUSTOMER_PRODUCT_SUSPENDED = 'bo.event.customer_product_suspended';
}
