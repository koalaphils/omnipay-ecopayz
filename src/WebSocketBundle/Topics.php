<?php

namespace WebSocketBundle;

final class Topics
{
    const TOPIC_CUSTOMER_PRODUCT_SAVE = 'bo.topic.customer_product_save';
    const TOPIC_CUSTOMER_PRODUCT_ACTIVATED = 'bo.topic.customer_product_activated';
    const TOPIC_CUSTOMER_PRODUCT_SUSPENDED = 'bo.topic.customer_product_suspended';
    const TOPIC_REFERRAL_LINKED = 'bo.topic.member_linked';
    const TOPIC_REFERRAL_UNLINKED = 'bo.topic.member_unlinked';
}