<?php

namespace MemberRequestBundle;

final class WebsocketTopics
{
    const TOPIC_MEMBER_REQUEST_SAVED = 'bo.topic.request.saved';
    const TOPIC_MEMBER_REQUEST_PROCESSED = 'bo.topic.member_request_processed';
    const TOPIC_MEMBER_REQUEST_GAUTH_PROCESSED = 'bo.topic.request.gauth_processed';
}
