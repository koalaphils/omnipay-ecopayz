<?php

namespace MemberBundle;

class Events
{
    const EVENT_MEMBER_CREATED = 'bo.event.customer_created';
    const MEMBER_COMMISSION_SAVE = 'bo.event.member_commission_save';
    const EVENT_REFERRAL_LINKED = 'bo.event.member_linked';
    const EVENT_REFERRAL_UNLINKED = 'bo.event.member_unlinked';
    const EVENT_MEMBER_PRODUCT_REQUESTED = 'bo.event.member_product.requested';
    const EVENT_MEMBER_KYC_FILE_UPLOADED = 'bo.event.kyc_file_uploaded';
    const EVENT_MEMBER_KYC_FILE_DELETED = 'bo.event.kyc_file_deleted';
    const MEMBER_VERIFICATION = 'bo.event.member_verification';
}
