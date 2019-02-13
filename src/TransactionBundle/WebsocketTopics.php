<?php

namespace TransactionBundle;

final class WebsocketTopics
{
    const TOPIC_TRANSACTION_PROCESSED = 'bo.topic.transaction_processed';
    const TOPIC_CUSTOMER_CREATED = 'bo.topic.customer_created';
    const TOPIC_DWL_UPDATE_PROCESS = 'bo.topic.dwl_process';
    const TOPIC_TRANSACTION_DECLINED = 'bo.topic.transaction_declined';
    const TOPIC_BITCOIN_RATE_TRANSACTION_EXPIRED = 'bo.topic.bitcoin_rate_transaction_expired';
}
