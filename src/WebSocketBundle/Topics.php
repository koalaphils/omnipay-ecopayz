<?php

namespace WebSocketBundle;

final class Topics
{
    const TOPIC_CUSTOMER_PRODUCT_SAVE = 'bo.topic.customer_product_save';
    const TOPIC_CUSTOMER_PRODUCT_ACTIVATED = 'bo.topic.customer_product_activated';
    const TOPIC_CUSTOMER_PRODUCT_SUSPENDED = 'bo.topic.customer_product_suspended';
    const TOPIC_REFERRAL_LINKED = 'bo.topic.member_linked';
    const TOPIC_REFERRAL_UNLINKED = 'bo.topic.member_unlinked';
    const TOPIC_BTC_EXCHANGE_RATE = 'btc.exchange_rate';
    const TOPIC_MEMBER_PRODUCT_REQUESTED = 'bo.topic.member_product.requested';
    const TOPIC_MEMBER_KYC_FILE_UPLOADED = 'bo.topic.kyc_file_uploaded';
    const TOPIC_MEMBER_KYC_FILE_DELETED = 'bo.topic.kyc_file_deleted';
    const TOPIC_MEMBER_API_KYC_UPLOADED = 'ms.topic.kyc_file_uploaded';
    const TOPIC_MEMBER_API_KYC_DELETED = 'ms.topic.kyc_file_deleted';
    const TOPIC_ADMIN_USER_LOGIN = 'bo.topic.admin_user_login';
    const TOPIC_TRANSACTION_DECLINED = 'bo.topic.transaction_declined';
}
