create table affiliate_member_mappings
(
    id                bigint unsigned auto_increment
        primary key,
    created_at        timestamp    null,
    updated_at        timestamp    null,
    affiliate_user_id int          not null,
    member_user_id    int          not null,
    referral_code     varchar(255) null
)
    collate = utf8mb4_unicode_ci;

create table affiliate_migrations
(
    id        int unsigned auto_increment
        primary key,
    migration varchar(255) not null,
    batch     int          not null
)
    collate = utf8mb4_unicode_ci;

create table affiliates
(
    id             bigint unsigned auto_increment
        primary key,
    created_at     timestamp    null,
    updated_at     timestamp    null,
    user_id        int          not null,
    email          varchar(255) not null,
    username       varchar(255) not null,
    name           varchar(255) not null,
    referral_codes json         not null,
    banners        json         not null
)
    collate = utf8mb4_unicode_ci;

create table announcement
(
    announcement_id          int unsigned auto_increment
        primary key,
    announcement_title       varchar(64)          not null,
    announcement_description longtext             not null,
    announcement_type        smallint unsigned    not null,
    announcement_start_at    datetime             not null,
    announcement_end_at      datetime             not null,
    announcement_is_active   tinyint(1) default 1 not null,
    announcement_created_by  bigint unsigned      not null,
    announcement_created_at  datetime             not null,
    announcement_updated_by  bigint unsigned      null,
    announcement_updated_at  datetime             null,
    announcement_image_uri   varchar(255)         null
)
    collate = utf8_unicode_ci;

create table banner_image
(
    banner_image_id         bigint unsigned auto_increment
        primary key,
    banner_image_language   varchar(5)           not null,
    banner_image_type       smallint             not null,
    banner_image_dimension  varchar(50)          not null,
    banner_image_details    json                 not null comment '(DC2Type:json)',
    banner_image_is_active  tinyint(1) default 1 not null,
    banner_image_updated_by bigint unsigned      null,
    banner_image_updated_at datetime             null,
    banner_image_created_by bigint unsigned      not null,
    banner_image_created_at datetime             not null
)
    collate = utf8_unicode_ci;

create index dimension_index
    on banner_image (banner_image_dimension);

create index language_index
    on banner_image (banner_image_language);

create index type_index
    on banner_image (banner_image_type);

create table banners
(
    id         bigint unsigned auto_increment
        primary key,
    created_at timestamp    null,
    updated_at timestamp    null,
    name       varchar(255) not null,
    type       varchar(255) not null,
    language   varchar(255) not null,
    paths      json         not null
)
    collate = utf8mb4_unicode_ci;

create table bitcoin_rate_setting
(
    bitcoin_rate_setting_id                    int unsigned auto_increment
        primary key,
    bitcoin_rate_setting_range_from            decimal(65, 10)  default 0.0000000000 null,
    bitcoin_rate_setting_range_to              decimal(65, 10)  default 0.0000000000 null,
    bitcoin_rate_setting_fixed_adjustment      decimal(65, 10)  default 0.0000000000 null,
    bitcoin_rate_setting_percentage_adjustment decimal(65, 10)  default 0.0000000000 null,
    bitcoin_rate_setting_is_default            tinyint(1)       default 0            not null,
    bitcoin_rate_setting_type                  tinyint unsigned default 1            not null comment '(DC2Type:tinyint)',
    bitcoin_rate_setting_created_by            bigint unsigned                       null,
    bitcoin_rate_setting_created_at            datetime                              not null,
    bitcoin_rate_setting_updated_by            bigint unsigned                       null,
    bitcoin_rate_setting_updated_at            datetime                              null
)
    collate = utf8_unicode_ci;

create table bonus
(
    bonus_id         bigint unsigned auto_increment
        primary key,
    bonus_subject    varchar(64)          not null,
    bonus_start_at   datetime             not null,
    bonus_end_at     datetime             not null,
    bonus_is_active  tinyint(1) default 1 not null,
    bonus_terms      longtext             not null,
    bonus_image      json                 null comment '(DC2Type:json)',
    bonus_deleted_at datetime             null,
    bonus_created_by bigint unsigned      not null,
    bonus_created_at datetime             not null,
    bonus_updated_by bigint unsigned      null,
    bonus_updated_at datetime             null
)
    collate = utf8_unicode_ci;

create table commission_period
(
    commission_period_id                   bigint unsigned auto_increment
        primary key,
    commission_period_date_from            datetime             not null comment '(DC2Type:datetime)',
    commission_period_date_to              datetime             not null comment '(DC2Type:datetime)',
    commission_period_payout_at            datetime             not null,
    commission_period_status               tinyint    default 1 not null comment '(DC2Type:tinyint)',
    commission_period_details              json                 not null comment '(DC2Type:json)',
    commission_period_conditions           json                 not null comment '(DC2Type:json)',
    commission_period_updated_by           bigint unsigned      null,
    commission_period_updated_at           datetime             null,
    commission_period_created_by           bigint unsigned      not null,
    commission_period_created_at           datetime             not null,
    commission_period_revenue_share_status tinyint(1) default 1 not null,
    commission_period_dwl_status           tinyint(1) default 1 not null,
    commission_period_dwl_updated_at       datetime             null,
    constraint UNIQ_ACF09D2231687BDB
        unique (commission_period_date_to),
    constraint UNIQ_ACF09D22DB4A524D
        unique (commission_period_date_from)
)
    collate = utf8_unicode_ci;

create table customer_group
(
    customer_group_id         int unsigned auto_increment
        primary key,
    customer_group_name       varchar(64)          not null,
    customer_is_default       tinyint(1) default 1 not null,
    customer_group_created_by bigint unsigned      not null,
    customer_group_created_at datetime             not null,
    customer_group_updated_by bigint unsigned      null,
    customer_group_updated_at datetime             null,
    constraint UNIQ_A3F531FE8AB021F4
        unique (customer_group_name)
)
    collate = utf8_unicode_ci;

create table customer_payment_options
(
    id                     bigint unsigned auto_increment
        primary key,
    customer_id            bigint                      not null,
    options                json default (json_array()) not null,
    payment_options_fields json                        not null,
    created_at             timestamp                   null,
    updated_at             timestamp                   null
)
    collate = utf8mb4_unicode_ci;

create table doctrine_migration_versions
(
    version        varchar(191) not null
        primary key,
    executed_at    datetime     null,
    execution_time int          null
)
    collate = utf8_unicode_ci;

create table ether_transactions
(
    id              bigint unsigned auto_increment
        primary key,
    created_at      timestamp                null,
    updated_at      timestamp                null,
    blockNumber     int                      not null,
    hash            varchar(255)             not null,
    `from`          varchar(255)             not null,
    `to`            varchar(255)             not null,
    contractAddress varchar(255)             not null,
    status          tinyint(1)   default 0   not null,
    value           varchar(255)             not null,
    tokenDecimal    int unsigned default '6' not null,
    timestamp       int                      not null,
    constraint ether_transactions_hash_unique
        unique (hash)
)
    collate = utf8mb4_unicode_ci;

create table ewl_account
(
    id          int auto_increment
        primary key,
    customer_id int      not null,
    preferences json     null,
    created_by  int      not null,
    created_at  datetime not null,
    updated_by  int      null,
    updated_at  datetime null
)
    collate = utf8_unicode_ci;

create table ewl_account_export_data
(
    id               bigint        null,
    customer_id      varchar(100)  null,
    entry_date       bigint        null,
    wallet_id        int           null,
    debit            double        null,
    credit           double        null,
    before_balance   double        null,
    after_balance    double        null,
    currency         char(3)       null,
    currency_ex_rate double        null,
    origin           varchar(100)  null,
    origin_id        varchar(50)   null,
    description      varchar(1000) null,
    commission_rate  double        null,
    unique_id        varchar(500)  null
)
    collate = utf8_unicode_ci;

create table ewl_account_export_data_error
(
    ID            bigint auto_increment
        primary key,
    ErrorText     varchar(5000) charset utf8         null,
    SourceID      varchar(500)                       null,
    ErrorSQLState varchar(100) charset utf8          null,
    CreatedDate   datetime default CURRENT_TIMESTAMP null
)
    charset = latin1;

create table ewl_alert_export_data_error
(
    ID            bigint auto_increment
        primary key,
    ErrorText     varchar(5000) charset utf8         null,
    SourceID      bigint                             null,
    ErrorSQLState varchar(100) charset utf8          null,
    CreatedDate   datetime default CURRENT_TIMESTAMP null
)
    charset = latin1;

create table ewl_commission_charges
(
    id                bigint auto_increment
        primary key,
    account_export_id bigint                             null,
    customer_id       varchar(100)                       null,
    market_id         varchar(50)                        null,
    commission_rate   double                             null,
    created_at        datetime default CURRENT_TIMESTAMP null
)
    collate = utf8_unicode_ci;

create index `ewl_commission_charges-account_export_id`
    on ewl_commission_charges (account_export_id);

create index `ewl_commission_charges-customer_market`
    on ewl_commission_charges (customer_id, market_id);

create table ewl_member_pnl
(
    unique_id              varchar(500) not null,
    bet_year               int          not null,
    bet_date               date         not null,
    bet_id                 varchar(50)  null,
    customer_id            varchar(100) not null,
    market_id              varchar(50)  not null,
    creation_date          datetime     null,
    placed_date            datetime     null,
    last_modified          datetime     null,
    event_type_name        varchar(150) not null,
    competition_name       varchar(150) null,
    event_name             varchar(300) not null,
    matched_date           datetime     null,
    cancelled_date         datetime     null,
    settled_date           datetime     null,
    selection_name         varchar(150) not null,
    price                  double       not null,
    side                   bit          not null,
    state                  int          not null,
    virtual_size_matched   double       not null,
    virtual_size_remaining double       not null,
    profit                 double       null,
    created_at             datetime     not null,
    updated_at             datetime     null,
    pregenerated_at        datetime     null,
    primary key (bet_year, bet_date, unique_id)
)
    collate = utf8_unicode_ci;

create index `ewl_member_pnl-customer_id`
    on ewl_member_pnl (bet_year, bet_date, customer_id);

create index `ewl_member_pnl-market_id`
    on ewl_member_pnl (market_id);

create index `ewl_member_pnl-unique_id`
    on ewl_member_pnl (unique_id);

create table ewl_offer_export_data
(
    id                     bigint       null,
    size                   int          null,
    price                  double       null,
    side                   bit          null,
    state                  int          null,
    profit                 double       null,
    resettled              bit          null,
    customer_id            varchar(100) null,
    bet_id                 varchar(50)  null,
    creation_date          bigint       null,
    placed_date            bigint       null,
    last_modified          bigint       null,
    wallet_id              int          null,
    selection_id           int          null,
    market_id              varchar(50)  null,
    event_id               varchar(50)  null,
    event_type_id          int          null,
    competition_name       varchar(150) null,
    country                varchar(100) null,
    event_type_name        varchar(150) null,
    event_name             varchar(300) null,
    market_name            varchar(100) null,
    selection_name         varchar(150) null,
    virtual_size           int          null,
    matched_date           bigint       null,
    avg_price_matched      double       null,
    size_matched           double       null,
    virtual_size_matched   double       null,
    size_remaining         double       null,
    virtual_size_remaining double       null,
    size_lapsed            double       null,
    virtual_size_lapsed    double       null,
    size_cancelled         double       null,
    virtual_size_cancelled double       null,
    size_voided            double       null,
    virtual_size_voided    double       null,
    wallet_currency        char(3)      null,
    virtual_profit         double       null,
    cancelled_date         bigint       null,
    settled_date           bigint       null,
    currency_ex_rate       double       null,
    currency_rate          double       null,
    currency_margin        double       null,
    in_play                bit          null,
    persistence_type       varchar(50)  null,
    betting_type           varchar(50)  null,
    handicap               double       null,
    market_start_time      bigint       null,
    market_type            varchar(50)  null,
    number_of_winners      int          null,
    each_way_divisor       int          null,
    cash_out               bit          null,
    cancelled_by_operator  bit          null,
    channel                varchar(50)  null,
    unique_id              varchar(500) null
)
    collate = utf8_unicode_ci;

create table ewl_offer_export_data_error
(
    ID            bigint auto_increment
        primary key,
    ErrorText     varchar(5000) charset utf8         null,
    SourceID      varchar(500) charset utf8          null,
    ErrorSQLState varchar(100) charset utf8          null,
    CreatedDate   datetime default CURRENT_TIMESTAMP null
)
    charset = latin1;

create table failed_jobs
(
    id         bigint unsigned auto_increment
        primary key,
    uuid       varchar(255)                        not null,
    connection text                                not null,
    queue      text                                not null,
    payload    longtext                            not null,
    exception  longtext                            not null,
    failed_at  timestamp default CURRENT_TIMESTAMP not null,
    constraint failed_jobs_uuid_unique
        unique (uuid)
)
    collate = utf8mb4_unicode_ci;

create table jms_cron_jobs
(
    id        int unsigned auto_increment
        primary key,
    command   varchar(200) not null,
    lastRunAt datetime     not null,
    constraint UNIQ_55F5ED428ECAEAD4
        unique (command)
)
    collate = utf8_unicode_ci;

create table jms_job_statistics
(
    job_id         bigint unsigned not null,
    characteristic varchar(30)     not null,
    createdAt      datetime        not null,
    charValue      double          not null,
    primary key (job_id, characteristic, createdAt)
)
    collate = utf8_unicode_ci;

create table jms_jobs
(
    id              bigint unsigned auto_increment
        primary key,
    state           varchar(15)       not null,
    queue           varchar(50)       not null,
    priority        smallint          not null,
    createdAt       datetime          not null,
    startedAt       datetime          null,
    checkedAt       datetime          null,
    workerName      varchar(50)       null,
    executeAfter    datetime          null,
    closedAt        datetime          null,
    command         varchar(255)      not null,
    args            json              not null comment '(DC2Type:json_array)',
    output          longtext          null,
    errorOutput     longtext          null,
    exitCode        smallint unsigned null,
    maxRuntime      smallint unsigned not null,
    maxRetries      smallint unsigned not null,
    stackTrace      longblob          null comment '(DC2Type:jms_job_safe_object)',
    runtime         smallint unsigned null,
    memoryUsage     int unsigned      null,
    memoryUsageReal int unsigned      null,
    originalJob_id  bigint unsigned   null,
    constraint FK_704ADB9349C447F1
        foreign key (originalJob_id) references jms_jobs (id)
)
    collate = utf8_unicode_ci;

create table jms_job_dependencies
(
    source_job_id bigint unsigned not null,
    dest_job_id   bigint unsigned not null,
    primary key (source_job_id, dest_job_id),
    constraint FK_8DCFE92C32CF8D4C
        foreign key (dest_job_id) references jms_jobs (id),
    constraint FK_8DCFE92CBD1F6B4F
        foreign key (source_job_id) references jms_jobs (id)
)
    collate = utf8_unicode_ci;

create index IDX_8DCFE92C32CF8D4C
    on jms_job_dependencies (dest_job_id);

create index IDX_8DCFE92CBD1F6B4F
    on jms_job_dependencies (source_job_id);

create table jms_job_related_entities
(
    job_id        bigint unsigned not null,
    related_class varchar(150)    not null,
    related_id    varchar(100)    not null,
    primary key (job_id, related_class, related_id),
    constraint FK_E956F4E2BE04EA9
        foreign key (job_id) references jms_jobs (id)
)
    collate = utf8_unicode_ci;

create index IDX_E956F4E2BE04EA9
    on jms_job_related_entities (job_id);

create index IDX_704ADB9349C447F1
    on jms_jobs (originalJob_id);

create index cmd_search_index
    on jms_jobs (command);

create index sorting_index
    on jms_jobs (state, priority, id);

create table job_batches
(
    id             varchar(255) not null
        primary key,
    name           varchar(255) not null,
    total_jobs     int          not null,
    pending_jobs   int          not null,
    failed_jobs    int          not null,
    failed_job_ids text         not null,
    options        mediumtext   null,
    cancelled_at   int          null,
    created_at     int          not null,
    finished_at    int          null
)
    collate = utf8mb4_unicode_ci;

create table jobs
(
    id           bigint unsigned auto_increment
        primary key,
    queue        varchar(255)     not null,
    payload      longtext         not null,
    attempts     tinyint unsigned not null,
    reserved_at  int unsigned     null,
    available_at int unsigned     not null,
    created_at   int unsigned     not null
)
    collate = utf8mb4_unicode_ci;

create index jobs_queue_index
    on jobs (queue);

create table member_pnl_report
(
    bet_year             int              not null,
    bet_date             date             not null,
    member_id            bigint           not null,
    ewl_total_bets       int    default 0 not null,
    ewl_total_turnover   double default 0 not null,
    ewl_total_pnl        double default 0 not null,
    ewl_total_commission double default 0 not null,
    pin_total_turnover   double default 0 not null,
    pin_total_pnl        double default 0 not null,
    evo_total_wager      double default 0 not null,
    evo_total_pnl        double default 0 not null
)
    collate = utf8_unicode_ci;

create index `member_pnl_report-bet_date_member_id`
    on member_pnl_report (bet_year, bet_date, member_id);

create table member_pnl_report_1
(
    bet_year             int    null,
    bet_date             date   null,
    member_id            bigint null,
    ewl_total_bets       int    null,
    ewl_total_turnover   double null,
    ewl_total_pnl        double null,
    ewl_total_commission double null,
    pin_total_turnover   double null,
    pin_total_pnl        double null,
    evo_total_wager      double null,
    evo_total_pnl        double null
);

create table member_pnl_report_pregen_error
(
    ID            bigint auto_increment
        primary key,
    ErrorText     varchar(5000) charset utf8         null,
    Source        varchar(100) charset utf8          null,
    ErrorSQLState varchar(100) charset utf8          null,
    CreatedDate   datetime default CURRENT_TIMESTAMP null
)
    charset = latin1;

create table member_promo
(
    member_promo_id             bigint unsigned auto_increment
        primary key,
    member_promo_promo_id       bigint unsigned not null,
    member_promo_referrer_id    bigint unsigned not null,
    member_promo_member_id      bigint unsigned not null,
    member_promo_transaction_id bigint unsigned null,
    member_promo_created_by     bigint unsigned not null,
    member_promo_created_at     datetime        not null,
    member_promo_updated_by     bigint unsigned null,
    member_promo_updated_at     datetime        null,
    constraint UNIQ_D2320F08BD061DE6
        unique (member_promo_transaction_id)
)
    collate = utf8_unicode_ci;

create index IDX_D2320F08E43229A7
    on member_promo (member_promo_referrer_id);

create index IDX_D2320F08FCF28674
    on member_promo (member_promo_promo_id);

create index IDX_D2320F08FD1BBAA
    on member_promo (member_promo_member_id);

create index mp_transaction_index
    on member_promo (member_promo_transaction_id);

create table migrations
(
    version varchar(255) not null
        primary key
)
    collate = utf8_unicode_ci;

create table migrations_payment_options
(
    id        int unsigned auto_increment
        primary key,
    migration varchar(255) not null,
    batch     int          not null
)
    collate = utf8mb4_unicode_ci;

create table migrations_transactions
(
    id        int unsigned auto_increment
        primary key,
    migration varchar(255) not null,
    batch     int          not null
)
    collate = utf8mb4_unicode_ci;

create table notification
(
    notification_id         bigint auto_increment
        primary key,
    notification_user_id    bigint       null,
    notification_message    varchar(255) null,
    notification_style      varchar(15)  not null,
    notification_created_at datetime     not null
)
    collate = utf8_unicode_ci;

create table oauth2_client
(
    id                  int auto_increment
        primary key,
    random_id           varchar(255) not null,
    redirect_uris       longtext     not null comment '(DC2Type:array)',
    secret              varchar(255) not null,
    allowed_grant_types longtext     not null comment '(DC2Type:array)'
)
    collate = utf8_unicode_ci;

create table payment_option
(
    payment_option_code             varchar(16)            not null
        primary key,
    payment_option_name             varchar(64)            not null,
    payment_mode                    varchar(64) default '' not null,
    payment_option_sort             varchar(5)  default '' not null,
    payment_option_fields           json                   not null comment '(DC2Type:json)',
    payment_option_image_uri        varchar(255)           null,
    payment_option_is_active        tinyint(1)  default 0  not null,
    payment_option_has_auto_decline tinyint(1)  default 0  not null,
    payment_option_created_by       bigint unsigned        null,
    payment_option_created_at       datetime               not null,
    payment_option_updated_by       bigint unsigned        null,
    payment_option_updated_at       datetime               null,
    payment_option_configs          json                   null comment '(DC2Type:json)',
    constraint UNIQ_7FBE9B2640A1043F
        unique (payment_option_name)
)
    collate = utf8_unicode_ci;

create table payment_options
(
    id         bigint unsigned auto_increment
        primary key,
    created_at timestamp                   null,
    updated_at timestamp                   null,
    code       varchar(255)                not null,
    name       varchar(255)                not null,
    provider   varchar(255)                not null,
    enabled    tinyint(1)                  not null,
    settings   json default (json_array()) not null
)
    collate = utf8mb4_unicode_ci;

create table piwi_system_log_sms_code
(
    sms_code_id                    varchar(36)  not null
        primary key,
    sms_code_value                 int          null,
    sms_code_json                  json         null comment '(DC2Type:json)',
    sms_code_customer_phone_number varchar(255) null,
    sms_code_customer_email        varchar(255) null,
    sms_code_status                varchar(1)   null,
    sms_code_created_at            int          null,
    sms_code_provider_id           varchar(1)   null,
    sms_code_source_phone_number   varchar(20)  null
)
    collate = utf8_unicode_ci;

create table product
(
    product_id         int unsigned auto_increment
        primary key,
    product_code       varchar(10)          not null,
    product_name       varchar(64)          not null,
    product_is_active  tinyint(1) default 1 not null,
    product_logo_uri   varchar(255)         null,
    product_url        varchar(255)         null,
    product_created_by bigint unsigned      not null,
    product_created_at datetime             not null,
    product_updated_by bigint unsigned      null,
    product_updated_at datetime             null,
    product_deleted_at datetime             null,
    product_details    json                 null comment '(DC2Type:json)',
    constraint UNIQ_D34A04ADFAFD1239
        unique (product_code),
    constraint name_unq
        unique (product_name)
)
    collate = utf8_unicode_ci;

create table product_commission
(
    product_commission_id          bigint unsigned auto_increment
        primary key,
    product_commission_product_id  int unsigned                         not null,
    product_commission_resource_id varchar(255)                         not null,
    product_commission_version     int unsigned    default 1            not null,
    product_commission_commission  decimal(65, 10) default 0.0000000000 not null,
    product_commission_is_latest   tinyint(1)      default 1            not null,
    product_commission_created_by  bigint unsigned                      not null,
    product_commission_created_at  datetime                             not null,
    constraint product_version_unq
        unique (product_commission_product_id, product_commission_version),
    constraint resource_version_unq
        unique (product_commission_resource_id, product_commission_version),
    constraint FK_8F025E73423FABDB
        foreign key (product_commission_product_id) references product (product_id)
)
    collate = utf8_unicode_ci;

create index isLatest_index
    on product_commission (product_commission_is_latest);

create index member_index
    on product_commission (product_commission_product_id);

create index resource_index
    on product_commission (product_commission_resource_id);

create index version_index
    on product_commission (product_commission_version);

create table promo
(
    promo_id         bigint unsigned auto_increment
        primary key,
    promo_code       varchar(50)                  not null,
    promo_name       varchar(255)                 not null,
    promo_status     tinyint unsigned default '0' not null,
    promo_details    json                         null comment '(DC2Type:json)',
    promo_created_by bigint unsigned              not null,
    promo_created_at datetime                     not null,
    promo_updated_by bigint unsigned              null,
    promo_updated_at datetime                     null,
    constraint UNIQ_B0139AFB14BADD00
        unique (promo_name),
    constraint UNIQ_B0139AFB3D8C939E
        unique (promo_code)
)
    collate = utf8_unicode_ci;

create index promo_index
    on promo (promo_status);

create table risk_setting
(
    risk_setting_id          int unsigned auto_increment
        primary key,
    risk_setting_risk_id     varchar(64)            not null,
    risk_setting_is_active   tinyint(1)   default 1 not null,
    risk_setting_resource_id varchar(255)           not null,
    risk_setting_version     int unsigned default 1 not null,
    risk_setting_is_latest   tinyint(1)   default 1 not null,
    risk_setting_created_at  datetime               not null
)
    collate = utf8_unicode_ci;

create table product_risk_setting
(
    product_risk_setting_id         int unsigned auto_increment
        primary key,
    product_risk_setting_risk_id    int unsigned    null,
    product_risk_setting_product_id int unsigned    null,
    product_risk_setting_percentage decimal(65, 10) not null,
    constraint FK_58CFF4BC8B44726F
        foreign key (product_risk_setting_product_id) references product (product_id),
    constraint FK_58CFF4BC8FC8A2E5
        foreign key (product_risk_setting_risk_id) references risk_setting (risk_setting_id)
)
    collate = utf8_unicode_ci;

create index IDX_58CFF4BC8B44726F
    on product_risk_setting (product_risk_setting_product_id);

create index IDX_58CFF4BC8FC8A2E5
    on product_risk_setting (product_risk_setting_risk_id);

create table setting
(
    setting_id    int unsigned auto_increment
        primary key,
    setting_code  varchar(64) not null,
    setting_value json        null comment '(DC2Type:json)',
    constraint UNIQ_9F74B898B6A11C7E
        unique (setting_code)
)
    collate = utf8_unicode_ci;

create table transaction_log
(
    transaction_log_id             bigint auto_increment
        primary key,
    transaction_log_transaction_id bigint               null,
    transaction_log_old_status     smallint             null,
    transaction_log_new_status     smallint             null,
    transaction_log_is_voided      tinyint(1) default 0 not null,
    transaction_log_created_by     bigint               null,
    transaction_log_created_at     datetime             not null
)
    collate = utf8_unicode_ci;

create table transaction_rule_sets
(
    id         bigint unsigned auto_increment
        primary key,
    name       varchar(255)         not null,
    enabled    tinyint(1) default 1 not null,
    settings   json                 null,
    rules      json                 null,
    created_at timestamp            null,
    updated_at timestamp            null,
    constraint transaction_rule_sets_name_unique
        unique (name)
)
    collate = utf8mb4_unicode_ci;

create table two_factor_code
(
    two_factory_code_id         char(36)     not null comment '(DC2Type:uuid)'
        primary key,
    two_factory_code_code       varchar(255) null,
    two_factory_code_payload    json         not null comment '(DC2Type:json)',
    two_factory_code_status     int          not null,
    two_factory_code_created_at datetime     not null comment '(DC2Type:datetimetz_immutable)',
    two_factory_code_expire_at  datetime     not null comment '(DC2Type:datetimetz_immutable)'
)
    collate = utf8_unicode_ci;

create index code_idx
    on two_factor_code (two_factory_code_code);

create table user_group
(
    group_id         int unsigned auto_increment
        primary key,
    group_name       varchar(64)     not null,
    group_roles      json            not null comment '(DC2Type:json)',
    group_created_by bigint unsigned not null,
    group_created_at datetime        not null,
    group_updated_by bigint unsigned null,
    group_updated_at datetime        null,
    constraint UNIQ_8F02BF9D77792576
        unique (group_name)
)
    collate = utf8_unicode_ci;

create table user
(
    user_id                            bigint unsigned auto_increment
        primary key,
    user_created_by                    bigint unsigned               null,
    user_group_id                      int unsigned                  null,
    user_username                      varchar(255) collate utf8_bin not null,
    user_password                      varchar(255)                  not null,
    user_email                         varchar(255)                  null,
    user_phone_number                  varchar(255)                  null,
    user_type                          int        default 1          not null,
    user_signup_type                   int        default 0          not null,
    user_is_active                     tinyint(1) default 1          not null,
    user_roles                         json                          null comment '(DC2Type:json)',
    user_deleted_at                    datetime                      null,
    user_zendesk_id                    bigint                        null,
    user_key                           varchar(255)                  null,
    user_activation_code               varchar(150)                  null,
    user_activation_sent_timestamp     datetime                      null,
    user_activation_timestamp          datetime                      null,
    user_created_at                    datetime                      not null,
    user_updated_by                    bigint unsigned               null,
    user_updated_at                    datetime                      null,
    user_preferences                   json                          not null comment '(DC2Type:json)',
    user_reset_password_code           varchar(150)                  null,
    user_reset_password_sent_timestamp datetime                      null,
    constraint email_unq
        unique (user_email, user_type),
    constraint username_unq
        unique (user_username, user_type),
    constraint FK_8D93D6491ED93D47
        foreign key (user_group_id) references user_group (group_id),
    constraint FK_8D93D64979756DBA
        foreign key (user_created_by) references user (user_id)
)
    collate = utf8_unicode_ci;

create table audit_revision
(
    audit_revision_id        bigint unsigned auto_increment
        primary key,
    audit_revision_user_id   bigint unsigned null,
    audit_revision_timestamp datetime        not null,
    audit_revision_client_ip varchar(255)    not null,
    constraint FK_3D774625AFD5FD7
        foreign key (audit_revision_user_id) references user (user_id)
)
    collate = utf8_unicode_ci;

create index IDX_3D774625AFD5FD7
    on audit_revision (audit_revision_user_id);

create index timestamp_index
    on audit_revision (audit_revision_timestamp);

create table audit_revision_log
(
    audit_revision_log_id                bigint unsigned auto_increment
        primary key,
    audit_revision_log_audit_revision_id bigint unsigned null,
    audit_revision_log_details           json            not null comment '(DC2Type:json)',
    audit_revision_log_operation         smallint        not null,
    audit_revision_log_category          smallint        not null,
    audit_revision_log_class_name        varchar(255) as (json_unquote(json_extract(`audit_revision_log_details`, _utf8mb3'$.class_name'))),
    audit_revision_log_identifier        varchar(255) as (json_unquote(json_extract(`audit_revision_log_details`, _utf8mb3'$.identifier'))),
    audit_revision_log_label             varchar(255) as (json_unquote(json_extract(`audit_revision_log_details`, _utf8mb3'$.label'))),
    constraint FK_19B97C61479B0D80
        foreign key (audit_revision_log_audit_revision_id) references audit_revision (audit_revision_id)
)
    collate = utf8_unicode_ci;

create index IDX_19B97C61479B0D80
    on audit_revision_log (audit_revision_log_audit_revision_id);

create index category_index
    on audit_revision_log (audit_revision_log_category);

create index className_index
    on audit_revision_log (audit_revision_log_class_name);

create index identifier_index
    on audit_revision_log (audit_revision_log_identifier);

create index label_index
    on audit_revision_log (audit_revision_log_label);

create index operation_index
    on audit_revision_log (audit_revision_log_operation);

create table currency
(
    currency_id         int unsigned auto_increment
        primary key,
    currency_updated_by bigint unsigned                      null,
    currency_code       varchar(5)                           not null,
    currency_name       varchar(250)                         not null,
    currency_rate       decimal(65, 10) default 1.0000000000 not null,
    currency_created_by bigint unsigned                      not null,
    currency_created_at datetime                             not null,
    currency_updated_at datetime                             null,
    constraint UNIQ_6956883FD4943D72
        unique (currency_name),
    constraint UNIQ_6956883FFDA273EC
        unique (currency_code),
    constraint FK_6956883FF519F7DA
        foreign key (currency_updated_by) references user (user_id)
)
    collate = utf8_unicode_ci;

create table country
(
    country_id          int unsigned auto_increment
        primary key,
    country_currency_id int unsigned    null,
    country_code        varchar(6)      not null,
    country_name        varchar(64)     not null,
    tags                json            not null comment '(DC2Type:json)',
    country_created_by  bigint unsigned not null,
    country_created_at  datetime        not null,
    country_updated_by  bigint unsigned null,
    country_updated_at  datetime        null,
    country_phone_code  varchar(6)      not null,
    country_locale      varchar(12)     not null,
    constraint UNIQ_5373C966D910F5E2
        unique (country_name),
    constraint UNIQ_5373C966F026BB7C
        unique (country_code),
    constraint FK_5373C9662DB3ABBD
        foreign key (country_currency_id) references currency (currency_id)
)
    collate = utf8_unicode_ci;

create index IDX_5373C9662DB3ABBD
    on country (country_currency_id);

create index IDX_6956883FF519F7DA
    on currency (currency_updated_by);

create table currency_rate
(
    currency_rate_id                      bigint unsigned auto_increment
        primary key,
    currency_rate_created_by              bigint unsigned                      not null,
    currency_rate_source_currency_id      int unsigned                         not null,
    currency_rate_destination_currency_id int unsigned                         not null,
    currency_rate_resource_id             varchar(255)                         not null,
    currency_rate_version                 int unsigned    default 1            not null,
    currency_rate_rate                    decimal(65, 10) default 1.0000000000 not null,
    currency_rate_destination_rate        decimal(65, 10) default 1.0000000000 not null,
    currency_rate_is_latest               tinyint(1)      default 1            not null,
    currency_rate_created_at              datetime                             not null,
    constraint currency_version_unq
        unique (currency_rate_source_currency_id, currency_rate_version),
    constraint resource_version_unq
        unique (currency_rate_resource_id, currency_rate_version),
    constraint FK_555B7C4D46B2FAAC
        foreign key (currency_rate_source_currency_id) references currency (currency_id),
    constraint FK_555B7C4DA13148D9
        foreign key (currency_rate_created_by) references user (user_id),
    constraint FK_555B7C4DBD3B7CB4
        foreign key (currency_rate_destination_currency_id) references currency (currency_id)
)
    collate = utf8_unicode_ci;

create index IDX_555B7C4D46B2FAAC
    on currency_rate (currency_rate_source_currency_id);

create index IDX_555B7C4DA13148D9
    on currency_rate (currency_rate_created_by);

create index IDX_555B7C4DBD3B7CB4
    on currency_rate (currency_rate_destination_currency_id);

create index isLatest_index
    on currency_rate (currency_rate_is_latest);

create index version_index
    on currency_rate (currency_rate_version);

create table customer
(
    customer_id                   bigint unsigned auto_increment
        primary key,
    customer_user_id              bigint unsigned                       not null,
    customer_currency_id          int unsigned                          null,
    customer_country_code         varchar(4)                            null,
    old_customer_affiliate_id     bigint unsigned                       null,
    customer_fname                varchar(64)      default ''           null,
    customer_mname                varchar(64)      default ''           null,
    customer_lname                varchar(64)      default ''           null,
    customer_full_name            varchar(150)                          not null,
    customer_is_customer          tinyint(1)       default 1            not null,
    customer_is_affiliate         tinyint(1)       default 0            not null,
    customer_birthdate            date                                  null,
    customer_gender               tinyint unsigned default '0'          not null comment '(DC2Type:tinyint)',
    customer_balance              decimal(65, 10)  default 0.0000000000 not null,
    customer_socials              json                                  not null comment '(DC2Type:json)',
    customer_transaction_password varchar(255)                          not null,
    customer_level                int              default 1            not null,
    customer_verified_at          datetime                              null,
    customer_other_details        json                                  not null comment '(DC2Type:json)',
    customer_joined_at            datetime                              not null,
    customer_files                json                                  not null comment '(DC2Type:json)',
    customer_contacts             json                                  not null comment '(DC2Type:json)',
    customer_notifications        json                                  null comment '(DC2Type:json)',
    customer_risk_setting         varchar(255)                          null,
    customer_tags                 json                                  not null comment '(DC2Type:json)',
    customer_pin_user_code        varchar(255)                          null,
    customer_pin_login_id         varchar(255)                          null,
    customer_locale               varchar(12)                           not null,
    customer_allow_revenue_share  tinyint(1)       default 0            not null,
    customer_affiliate_id         bigint                                null,
    constraint UNIQ_81398E09BBB3772B
        unique (customer_user_id),
    constraint FK_81398E093B6FAA7E
        foreign key (customer_currency_id) references currency (currency_id),
    constraint FK_81398E09BBB3772B
        foreign key (customer_user_id) references user (user_id)
)
    collate = utf8_unicode_ci;

create index IDX_81398E093B6FAA7E
    on customer (customer_currency_id);

create index IDX_81398E0991CA0783
    on customer (old_customer_affiliate_id);

create index fname_index
    on customer (customer_fname);

create index fullname_index
    on customer (customer_full_name);

create index lname_index
    on customer (customer_lname);

create table customer_groups
(
    ccg_customer_id       bigint unsigned not null,
    ccg_customer_group_id int unsigned    not null,
    primary key (ccg_customer_id, ccg_customer_group_id),
    constraint FK_41A8E52181036024
        foreign key (ccg_customer_group_id) references customer_group (customer_group_id),
    constraint FK_41A8E521B151EDEB
        foreign key (ccg_customer_id) references customer (customer_id)
)
    collate = utf8_unicode_ci;

create index IDX_41A8E52181036024
    on customer_groups (ccg_customer_group_id);

create index IDX_41A8E521B151EDEB
    on customer_groups (ccg_customer_id);

create table customer_payment_option
(
    customer_payment_option_id           bigint auto_increment
        primary key,
    customer_payment_option_type         varchar(16)     null,
    customer_payment_options_customer_id bigint unsigned null,
    customer_payment_option_is_active    tinyint(1)      not null,
    customer_payment_option_fields       json            not null comment '(DC2Type:json)',
    customer_payment_option_created_by   bigint unsigned null,
    customer_payment_option_created_at   datetime        not null,
    customer_payment_option_updated_by   bigint unsigned null,
    customer_payment_option_updated_at   datetime        null,
    constraint FK_E6D3AFEC2CB77FFD
        foreign key (customer_payment_options_customer_id) references customer (customer_id),
    constraint FK_E6D3AFECAE3CDBBC
        foreign key (customer_payment_option_type) references payment_option (payment_option_code)
)
    collate = utf8_unicode_ci;

create index IDX_E6D3AFEC2CB77FFD
    on customer_payment_option (customer_payment_options_customer_id);

create index IDX_E6D3AFECAE3CDBBC
    on customer_payment_option (customer_payment_option_type);

create table customer_product
(
    cproduct_id           bigint unsigned auto_increment
        primary key,
    cproduct_customer_id  bigint unsigned                      not null,
    cproduct_product_id   int unsigned                         not null,
    cproduct_username     varchar(100)                         not null,
    cproduct_balance      decimal(65, 10) default 0.0000000000 not null,
    cproduct_is_active    tinyint(1)      default 1            not null,
    cproduct_created_by   bigint unsigned                      not null,
    cproduct_created_at   datetime                             not null,
    cproduct_updated_by   bigint unsigned                      null,
    cproduct_updated_at   datetime                             null,
    cproduct_details      json                                 null comment '(DC2Type:json)',
    cproduct_bet_sync_id  bigint as (json_extract(`cproduct_details`, _utf8mb3'$.brokerage.sync_id')),
    cproduct_requested_at datetime                             null,
    constraint username_unq
        unique (cproduct_username, cproduct_product_id),
    constraint FK_CF97A0136ACAB2D9
        foreign key (cproduct_customer_id) references customer (customer_id),
    constraint FK_CF97A013A32A23C6
        foreign key (cproduct_product_id) references product (product_id)
)
    collate = utf8_unicode_ci;

create index IDX_CF97A0136ACAB2D9
    on customer_product (cproduct_customer_id);

create index bet_sync_id_index
    on customer_product (cproduct_bet_sync_id);

create index productID_index
    on customer_product (cproduct_product_id);

create index userName_index
    on customer_product (cproduct_username);

create table dwl
(
    dwl_id          bigint unsigned auto_increment
        primary key,
    dwl_product_id  int unsigned                                not null,
    dwl_currency_id int unsigned                                not null,
    dwl_status      smallint unsigned default 1                 not null,
    dwl_version     smallint unsigned default 1                 not null,
    dwl_date        date                                        not null,
    dwl_details     json                                        not null comment '(DC2Type:json)',
    dwl_created_by  bigint unsigned                             not null,
    dwl_created_at  datetime                                    not null,
    dwl_updated_by  int unsigned                                null,
    dwl_updated_at  timestamp         default CURRENT_TIMESTAMP not null,
    constraint FK_94E578AC1936ACA0
        foreign key (dwl_currency_id) references currency (currency_id),
    constraint FK_94E578ACE35D056E
        foreign key (dwl_product_id) references product (product_id)
)
    collate = utf8_unicode_ci;

create index IDX_94E578AC1936ACA0
    on dwl (dwl_currency_id);

create index IDX_94E578ACE35D056E
    on dwl (dwl_product_id);

create index date_index
    on dwl (dwl_date);

create index status_index
    on dwl (dwl_status);

create table gateway
(
    gateway_id             int unsigned auto_increment
        primary key,
    gateway_payment_option varchar(255)                         null,
    gateway_currency_id    int unsigned                         not null,
    gateway_name           varchar(64)                          not null,
    gateway_balance        decimal(65, 10) default 0.0000000000 not null,
    gateway_is_active      tinyint(1)      default 1            not null,
    gateway_created_by     bigint unsigned                      not null,
    gateway_created_at     datetime                             not null,
    gateway_updated_by     bigint unsigned                      null,
    gateway_updated_at     datetime                             null,
    gateway_details        json                                 not null comment '(DC2Type:json)',
    gateway_levels         json                                 not null comment '(DC2Type:json)',
    constraint FK_14FEDD7FB2527E6
        foreign key (gateway_currency_id) references currency (currency_id)
)
    collate = utf8_unicode_ci;

create table customer_group_gateway
(
    cgg_gateway_id        int unsigned    not null,
    cgg_customer_group_id int unsigned    not null,
    cgg_conditions        longtext        not null,
    cgg_created_by        bigint unsigned not null,
    cgg_created_at        datetime        not null,
    cgg_updated_by        bigint unsigned null,
    cgg_updated_at        datetime        null,
    primary key (cgg_gateway_id, cgg_customer_group_id),
    constraint FK_F69BF97051FE4520
        foreign key (cgg_gateway_id) references gateway (gateway_id),
    constraint FK_F69BF970CFEE8B7D
        foreign key (cgg_customer_group_id) references customer_group (customer_group_id)
)
    collate = utf8_unicode_ci;

create index IDX_F69BF97051FE4520
    on customer_group_gateway (cgg_gateway_id);

create index IDX_F69BF970CFEE8B7D
    on customer_group_gateway (cgg_customer_group_id);

create index IDX_14FEDD7FB2527E6
    on gateway (gateway_currency_id);

create index IDX_14FEDD7FDF7081C
    on gateway (gateway_payment_option);

create index UNIQ_20170719002312
    on gateway (gateway_name, gateway_currency_id);

create table gateway_log
(
    gateway_log_id                   bigint unsigned auto_increment
        primary key,
    gateway_log_gateway_id           int unsigned                         null,
    gateway_log_currency_id          int unsigned                         null,
    gateway_log_payment_option_code  varchar(16)                          null,
    gateway_log_timestamp            datetime                             not null,
    gateway_log_type                 smallint                             not null,
    gateway_log_balance              decimal(65, 10) default 0.0000000000 not null,
    gateway_log_amount               decimal(65, 10) default 0.0000000000 not null,
    gateway_log_reference_number     varchar(255)                         not null,
    gateway_log_details              json                                 null comment '(DC2Type:json)',
    gateway_log_reference_class      varchar(255) as (json_unquote(json_extract(`gateway_log_details`, _utf8mb3'$.reference_class'))),
    gateway_log_reference_identifier varchar(255) as (json_unquote(json_extract(`gateway_log_details`, _utf8mb3'$.identifier'))),
    constraint FK_E4BC7FD82F20B181
        foreign key (gateway_log_gateway_id) references gateway (gateway_id),
    constraint FK_E4BC7FD8A2E36DFF
        foreign key (gateway_log_currency_id) references currency (currency_id)
)
    collate = utf8_unicode_ci;

create index IDX_E4BC7FD82F20B181
    on gateway_log (gateway_log_gateway_id);

create index IDX_E4BC7FD876B8098E
    on gateway_log (gateway_log_payment_option_code);

create index IDX_E4BC7FD8A2E36DFF
    on gateway_log (gateway_log_currency_id);

create index reference_class_index
    on gateway_log (gateway_log_reference_class);

create index reference_indentifier_index
    on gateway_log (gateway_log_reference_identifier);

create table gateway_transaction
(
    gateway_transaction_id                  bigint unsigned auto_increment
        primary key,
    gateway_transaction_gateway_id          int unsigned                              null,
    gateway_transaction_gateway_to_id       int unsigned                              null,
    gateway_transaction_currency_id         int unsigned                              not null,
    gateway_transaction_payment_option_code varchar(16)                               null,
    gateway_transaction_number              varchar(255)                              not null,
    gateway_transaction_type                smallint                                  not null,
    gateway_transaction_date                datetime                                  not null,
    gateway_transaction_amount              decimal(65, 10) default 0.0000000000      not null,
    gateway_transaction_amount_to           decimal(65, 10) default 0.0000000000      null,
    gateway_transaction_fees                json                                      not null comment '(DC2Type:json)',
    gateway_transaction_status              smallint unsigned                         not null,
    gateway_transaction_details             json                                      null comment '(DC2Type:json)',
    gateway_transaction_is_voided           tinyint(1)      default 0                 not null,
    gateway_transaction_created_by          bigint unsigned                           not null,
    gateway_transaction_created_at          datetime                                  not null,
    gateway_transaction_updated_by          bigint unsigned                           null,
    gateway_transaction_updated_at          timestamp       default CURRENT_TIMESTAMP null,
    constraint UNIQ_4136A3411F9E9724
        unique (gateway_transaction_number),
    constraint FK_4136A3417D60C973
        foreign key (gateway_transaction_gateway_to_id) references gateway (gateway_id),
    constraint FK_4136A3419C4F165
        foreign key (gateway_transaction_currency_id) references currency (currency_id),
    constraint FK_4136A341D98172AE
        foreign key (gateway_transaction_gateway_id) references gateway (gateway_id)
)
    collate = utf8_unicode_ci;

create index IDX_4136A3417D60C973
    on gateway_transaction (gateway_transaction_gateway_to_id);

create index IDX_4136A3419C4F165
    on gateway_transaction (gateway_transaction_currency_id);

create index IDX_4136A341D98172AE
    on gateway_transaction (gateway_transaction_gateway_id);

create index IDX_4136A341FDCE26F6
    on gateway_transaction (gateway_transaction_payment_option_code);

create table inactive_member
(
    inactive_id              bigint unsigned auto_increment
        primary key,
    inactive_member_id       bigint unsigned null,
    inactive_member_added_at datetime        not null,
    constraint UNIQ_4470EE2FC5A86BF7
        unique (inactive_member_id),
    constraint FK_4470EE2FC5A86BF7
        foreign key (inactive_member_id) references customer (customer_id)
)
    collate = utf8_unicode_ci;

create index member_index
    on inactive_member (inactive_member_id);

create table marketing_tool
(
    marketing_tool_id             bigint unsigned auto_increment
        primary key,
    marketing_tool_member_id      bigint unsigned         null,
    marketing_tool_affiliate_link varchar(255)            not null,
    marketing_tool_promo_code     varchar(255) default '' null,
    marketing_tool_resource_id    varchar(255)            not null,
    marketing_tool_version        int unsigned default 1  not null,
    marketing_tool_is_latest      tinyint(1)   default 1  not null,
    marketing_tool_created_by     bigint unsigned         not null,
    marketing_tool_created_at     datetime                not null,
    constraint FK_A2D4DC89824B58A4
        foreign key (marketing_tool_member_id) references customer (customer_id)
)
    collate = utf8_unicode_ci;

create index IDX_A2D4DC89824B58A4
    on marketing_tool (marketing_tool_member_id);

create table member_commission
(
    member_commission_id          bigint unsigned auto_increment
        primary key,
    member_commission_member_id   bigint unsigned                        not null,
    member_commission_product_id  int unsigned                           not null,
    member_commission_resource_id varchar(255)                           not null,
    member_commission_version     int unsigned      default 1            not null,
    member_commission_commission  decimal(65, 10)   default 0.0000000000 not null,
    member_commission_status      smallint unsigned default 1            not null,
    member_commission_is_latest   tinyint(1)        default 1            not null,
    member_commission_created_by  bigint unsigned                        not null,
    member_commission_created_at  datetime                               not null,
    constraint resource_version_unq
        unique (member_commission_resource_id, member_commission_version),
    constraint FK_F72B1FB12F836513
        foreign key (member_commission_member_id) references customer (customer_id),
    constraint FK_F72B1FB19B65EC29
        foreign key (member_commission_product_id) references product (product_id)
)
    collate = utf8_unicode_ci;

create index IDX_F72B1FB19B65EC29
    on member_commission (member_commission_product_id);

create index isLatest_index
    on member_commission (member_commission_is_latest);

create index member_index
    on member_commission (member_commission_member_id);

create index resource_index
    on member_commission (member_commission_resource_id);

create index status_index
    on member_commission (member_commission_status);

create index version_index
    on member_commission (member_commission_version);

create table member_referral_name
(
    member_referral_name_id         bigint unsigned auto_increment
        primary key,
    member_referral_name_member_id  bigint unsigned      null,
    member_referral_name_name       varchar(100)         not null,
    member_referral_name_is_active  tinyint(1) default 1 not null,
    member_referral_name_updated_by bigint unsigned      null,
    member_referral_name_updated_at datetime             null,
    member_referral_name_created_by bigint unsigned      not null,
    member_referral_name_created_at datetime             not null,
    constraint name_unq
        unique (member_referral_name_name),
    constraint FK_AE67B3582E9B4996
        foreign key (member_referral_name_member_id) references customer (customer_id)
)
    collate = utf8_unicode_ci;

create index member_index
    on member_referral_name (member_referral_name_member_id);

create table member_request
(
    member_request_id         bigint unsigned auto_increment
        primary key,
    member_request_member_id  bigint unsigned                     null,
    member_request_number     varchar(255)                        not null,
    member_request_date       datetime                            not null,
    member_request_type       int unsigned                        not null,
    member_request_status     int unsigned                        not null,
    member_request_details    json                                null comment '(DC2Type:json)',
    member_request_created_by bigint unsigned                     not null,
    member_request_created_at datetime                            not null,
    member_request_updated_by bigint unsigned                     null,
    member_request_updated_at timestamp default CURRENT_TIMESTAMP null,
    constraint UNIQ_8463380B3924051C
        unique (member_request_number),
    constraint FK_8463380B773F8267
        foreign key (member_request_member_id) references customer (customer_id)
)
    collate = utf8_unicode_ci;

create index IDX_8463380B773F8267
    on member_request (member_request_member_id);

create index UNIQ_20190328103500
    on member_request (member_request_number);

create index member_request_type
    on member_request (member_request_type);

create table member_revenue_share
(
    member_revenue_share_id          bigint unsigned auto_increment
        primary key,
    member_revenue_share_member_id   bigint unsigned             not null,
    member_revenue_share_product_id  int unsigned                not null,
    member_revenue_share_resource_id varchar(255)                not null,
    member_revenue_share_settings    json                        not null comment '(DC2Type:json)',
    member_revenue_share_version     int unsigned      default 1 not null,
    member_revenue_share_status      smallint unsigned default 1 not null,
    member_revenue_share_is_latest   tinyint(1)        default 1 not null,
    member_revenue_share_created_by  bigint unsigned             not null,
    member_revenue_share_created_at  datetime                    not null comment '(DC2Type:datetime)',
    constraint resource_version_unq
        unique (member_revenue_share_resource_id, member_revenue_share_version),
    constraint FK_DA010DB8977C02EC
        foreign key (member_revenue_share_member_id) references customer (customer_id),
    constraint FK_DA010DB8B6DFFCC3
        foreign key (member_revenue_share_product_id) references product (product_id)
)
    collate = utf8_unicode_ci;

create index isLatest_index
    on member_revenue_share (member_revenue_share_is_latest);

create index member_index
    on member_revenue_share (member_revenue_share_member_id);

create index product_index
    on member_revenue_share (member_revenue_share_product_id);

create index resource_index
    on member_revenue_share (member_revenue_share_resource_id);

create index status_index
    on member_revenue_share (member_revenue_share_status);

create index version_index
    on member_revenue_share (member_revenue_share_version);

create table member_website
(
    member_website_id         bigint unsigned auto_increment
        primary key,
    member_website_member_id  bigint unsigned      null,
    member_website_website    varchar(100)         not null,
    member_website_is_active  tinyint(1) default 1 not null,
    member_website_updated_by bigint unsigned      null,
    member_website_updated_at datetime             null,
    member_website_created_by bigint unsigned      not null,
    member_website_created_at datetime             not null,
    constraint website_unq
        unique (member_website_website),
    constraint FK_F89BEA73AF169B14
        foreign key (member_website_member_id) references customer (customer_id)
)
    collate = utf8_unicode_ci;

create table member_banner
(
    member_banner_id               bigint unsigned auto_increment
        primary key,
    member_banner_website_id       bigint unsigned null,
    member_banner_referral_name_id bigint unsigned null,
    member_banner_image_id         bigint unsigned null,
    member_banner_member_id        bigint unsigned null,
    member_banner_campaign_name    varchar(100)    not null,
    member_banner_created_by       bigint unsigned not null,
    member_banner_created_at       datetime        not null,
    constraint campaign_name_unq
        unique (member_banner_campaign_name),
    constraint FK_4B4B3AD41D404BBA
        foreign key (member_banner_referral_name_id) references member_referral_name (member_referral_name_id),
    constraint FK_4B4B3AD46D10E4A1
        foreign key (member_banner_image_id) references banner_image (banner_image_id),
    constraint FK_4B4B3AD493D86653
        foreign key (member_banner_website_id) references member_website (member_website_id),
    constraint FK_4B4B3AD4E715E8A4
        foreign key (member_banner_member_id) references customer (customer_id)
)
    collate = utf8_unicode_ci;

create index IDX_4B4B3AD46D10E4A1
    on member_banner (member_banner_image_id);

create index member_index
    on member_banner (member_banner_member_id);

create index referral_name_index
    on member_banner (member_banner_referral_name_id);

create index website_index
    on member_banner (member_banner_website_id);

create index member_index
    on member_website (member_website_member_id);

create table oauth2_access_token
(
    id         int auto_increment
        primary key,
    client_id  int                     null,
    user_id    bigint unsigned         null,
    token      varchar(500)            null,
    expires_at int                     null,
    scope      varchar(255)            null,
    ip_address varchar(255) default '' not null,
    constraint UNIQ_454D96735F37A13B
        unique (token),
    constraint FK_454D967319EB6921
        foreign key (client_id) references oauth2_client (id),
    constraint FK_454D9673A76ED395
        foreign key (user_id) references user (user_id)
)
    collate = utf8_unicode_ci;

create index IDX_454D967319EB6921
    on oauth2_access_token (client_id);

create index IDX_454D9673A76ED395
    on oauth2_access_token (user_id);

create table oauth2_auth_code
(
    id           int auto_increment
        primary key,
    client_id    int             null,
    user_id      bigint unsigned null,
    token        varchar(255)    not null,
    redirect_uri longtext        not null,
    expires_at   int             null,
    scope        varchar(255)    null,
    constraint UNIQ_1D2905B55F37A13B
        unique (token),
    constraint FK_1D2905B519EB6921
        foreign key (client_id) references oauth2_client (id),
    constraint FK_1D2905B5A76ED395
        foreign key (user_id) references user (user_id)
)
    collate = utf8_unicode_ci;

create index IDX_1D2905B519EB6921
    on oauth2_auth_code (client_id);

create index IDX_1D2905B5A76ED395
    on oauth2_auth_code (user_id);

create table oauth2_refresh_token
(
    id         int auto_increment
        primary key,
    client_id  int             null,
    user_id    bigint unsigned null,
    token      varchar(255)    not null,
    expires_at int             null,
    scope      varchar(255)    null,
    constraint UNIQ_4DD907325F37A13B
        unique (token),
    constraint FK_4DD9073219EB6921
        foreign key (client_id) references oauth2_client (id),
    constraint FK_4DD90732A76ED395
        foreign key (user_id) references user (user_id)
)
    collate = utf8_unicode_ci;

create index IDX_4DD9073219EB6921
    on oauth2_refresh_token (client_id);

create index IDX_4DD90732A76ED395
    on oauth2_refresh_token (user_id);

create table session
(
    id                 bigint unsigned auto_increment
        primary key,
    session_user_id    bigint unsigned not null,
    session_id         varchar(255)    not null,
    session_key        varchar(255)    not null,
    session_created_at datetime        not null,
    session_details    json            not null comment '(DC2Type:json)',
    constraint FK_D044D5D4B5B651CF
        foreign key (session_user_id) references user (user_id)
)
    collate = utf8_unicode_ci;

create index IDX_D044D5D4B5B651CF
    on session (session_user_id);

create table transaction
(
    transaction_id                                       bigint unsigned auto_increment
        primary key,
    transaction_created_by                               bigint unsigned                           null,
    transaction_customer_id                              bigint unsigned                           not null,
    transaction_gateway_id                               int unsigned                              null,
    transaction_currency_id                              int unsigned                              not null,
    transaction_payment_option_id                        bigint                                    null,
    transaction_payment_option_on_transaction_id         bigint                                    null,
    transaction_number                                   varchar(255)                              not null,
    transaction_amount                                   decimal(65, 10) default 0.0000000000      not null,
    transaction_fees                                     json                                      not null comment '(DC2Type:json)',
    transaction_type                                     smallint                                  not null,
    transaction_date                                     datetime                                  not null,
    transaction_status                                   smallint unsigned                         not null,
    transaction_is_voided                                tinyint(1)      default 0                 not null,
    transaction_other_details                            json                                      null comment '(DC2Type:json)',
    transaction_created_at                               datetime                                  not null,
    transaction_updated_by                               bigint unsigned                           null,
    transaction_updated_at                               timestamp       default CURRENT_TIMESTAMP null,
    transaction_deleted_at                               datetime                                  null,
    transaction_to_customer                              bigint as (json_extract(`transaction_other_details`, _utf8mb3'$.toCustomer')),
    dwl_id                                               bigint as (json_extract(`transaction_other_details`, _utf8mb3'$.dwl.id')),
    transaction_bet_id                                   bigint as (json_extract(`transaction_other_details`, _utf8mb3'$.bet_id')),
    transaction_bet_event_id                             bigint as (json_extract(`transaction_other_details`, _utf8mb3'$.event_id')),
    transaction_commission_computed_original             decimal(65, 10) as (json_extract(`transaction_other_details`,
        _utf8mb3'$.commission.computed.original')),
    transaction_finished_at                              datetime                                  null,
    transaction_virtual_bitcoin_transaction_hash         varchar(255) as (json_unquote(json_extract(
        `transaction_other_details`, _utf8mb3'$.bitcoin.transaction.hash'))),
    transaction_bitcoin_confirmation_count               int as (json_unquote(json_extract(`transaction_other_details`,
        _utf8mb3'$.bitcoin.confirmation_count'))),
    transaction_virtual_bitcoin_sender_address           json as (json_unquote(json_extract(`transaction_other_details`,
        _utf8mb3'$.bitcoin.transaction.sender_address'))) comment '(DC2Type:json)',
    transaction_virtual_bitcoin_receiver_unique_address  varchar(255) as (json_unquote(json_extract(
        `transaction_other_details`, _utf8mb3'$.bitcoin.transaction.receiver_unique_address'))),
    transaction_bitcoin_confirmation                     int as (json_extract(`transaction_other_details`,
        _utf8mb3'$.bitcoin.confirmation_count')),
    transaction_email                                    varchar(255) as (json_unquote(json_extract(`transaction_other_details`, _utf8mb3'$.email'))),
    transaction_virtual_bitcoin_is_acknowledge_by_member tinyint(1) as (if(
        (json_unquote(json_extract(`transaction_other_details`, _utf8mb3'$.bitcoin.acknowledged_by_user')) =
        _utf8mb4'true'), 1, 0)),
    transaction_payment_option_type                      varchar(255) as (json_unquote(json_extract(
        `transaction_other_details`, _utf8mb3'$.paymentOptionOnTransaction.code'))),
    constraint UNIQ_723705D1E0ED6D14
        unique (transaction_number),
    constraint FK_723705D12F1CD6DE
        foreign key (transaction_customer_id) references customer (customer_id),
    constraint FK_723705D131849CE7
        foreign key (transaction_created_by) references user (user_id),
    constraint FK_723705D1682A26E5
        foreign key (transaction_payment_option_on_transaction_id) references customer_payment_option (customer_payment_option_id),
    constraint FK_723705D184AD945B
        foreign key (transaction_currency_id) references currency (currency_id),
    constraint FK_723705D1ABCAC298
        foreign key (transaction_payment_option_id) references customer_payment_option (customer_payment_option_id),
    constraint FK_723705D1B8E9B9B1
        foreign key (transaction_gateway_id) references gateway (gateway_id)
)
    collate = utf8_unicode_ci;

create table member_running_commission
(
    member_running_commission_id             bigint unsigned auto_increment
        primary key,
    member_running_commission_transaction_id bigint unsigned                      null,
    member_running_commission_preceeding_id  bigint unsigned                      null,
    member_running_commission_succeeding_id  bigint unsigned                      null,
    member_running_commission_cproduct_id    bigint unsigned                      null,
    member_running_commission_period_id      bigint unsigned                      null,
    member_running_commission_total          decimal(65, 10) default 0.0000000000 not null,
    member_running_commission_commission     decimal(65, 10) default 0.0000000000 not null,
    member_running_commission_status         varchar(255)                         not null,
    member_running_commission_process_status tinyint         default 1            not null comment '(DC2Type:tinyint)',
    member_running_commission_metadata       json                                 not null comment '(DC2Type:metadata)',
    member_running_commission_updated_by     bigint unsigned                      null,
    member_running_commission_updated_at     datetime                             null,
    member_running_commission_created_by     bigint unsigned                      not null,
    member_running_commission_created_at     datetime                             not null,
    constraint UNIQ_D2E61304CFE15BD1
        unique (member_running_commission_succeeding_id),
    constraint UNIQ_D2E61304DDED61B3
        unique (member_running_commission_transaction_id),
    constraint UNIQ_D2E61304EE0614D3
        unique (member_running_commission_preceeding_id),
    constraint FK_D2E6130444523B87
        foreign key (member_running_commission_period_id) references commission_period (commission_period_id),
    constraint FK_D2E613046E1D703B
        foreign key (member_running_commission_cproduct_id) references customer_product (cproduct_id),
    constraint FK_D2E61304CFE15BD1
        foreign key (member_running_commission_succeeding_id) references member_running_commission (member_running_commission_id),
    constraint FK_D2E61304DDED61B3
        foreign key (member_running_commission_transaction_id) references transaction (transaction_id),
    constraint FK_D2E61304EE0614D3
        foreign key (member_running_commission_preceeding_id) references member_running_commission (member_running_commission_id)
)
    collate = utf8_unicode_ci;

create index IDX_D2E6130444523B87
    on member_running_commission (member_running_commission_period_id);

create index IDX_D2E613046E1D703B
    on member_running_commission (member_running_commission_cproduct_id);

create table member_running_revenue_share
(
    member_running_revenue_share_id             varchar(255)                         not null
        primary key,
    member_running_revenue_share_total          decimal(65, 10) default 0.0000000000 not null,
    member_running_revenue_share_revenue_share  decimal(65, 10) default 0.0000000000 not null,
    member_running_revenue_share_status         varchar(255)                         not null,
    member_running_revenue_share_process_status tinyint                              not null,
    member_running_revenue_share_member_id      bigint unsigned                      not null,
    member_running_revenue_share_period_id      bigint unsigned                      not null,
    member_running_revenue_share_transaction_id bigint unsigned                      null,
    member_running_revenue_share_preceding_id   varchar(255)                         null,
    member_running_revenue_share_succeeding_id  varchar(255)                         null,
    member_running_revenue_share_metadata       json                                 not null,
    member_running_revenue_share_created_by     bigint unsigned                      not null,
    member_running_revenue_share_created_at     datetime                             not null,
    member_running_revenue_share_updated_by     bigint unsigned                      null,
    member_running_revenue_share_updated_at     datetime                             null,
    constraint IDX_period_member
        unique (member_running_revenue_share_period_id, member_running_revenue_share_member_id),
    constraint IDX_transaction_id
        unique (member_running_revenue_share_transaction_id),
    constraint member_running_revenue_share_ibfk_1
        foreign key (member_running_revenue_share_member_id) references customer (customer_id)
            on update cascade,
    constraint member_running_revenue_share_ibfk_2
        foreign key (member_running_revenue_share_period_id) references commission_period (commission_period_id)
            on update cascade,
    constraint member_running_revenue_share_ibfk_3
        foreign key (member_running_revenue_share_transaction_id) references transaction (transaction_id)
            on update cascade on delete set null,
    constraint member_running_revenue_share_ibfk_4
        foreign key (member_running_revenue_share_preceding_id) references member_running_revenue_share (member_running_revenue_share_id)
            on update cascade on delete set null,
    constraint member_running_revenue_share_ibfk_5
        foreign key (member_running_revenue_share_succeeding_id) references member_running_revenue_share (member_running_revenue_share_id)
            on update cascade on delete set null
);

create index IDX_preceding_id
    on member_running_revenue_share (member_running_revenue_share_preceding_id);

create index IDX_process_status
    on member_running_revenue_share (member_running_revenue_share_process_status);

create index IDX_status
    on member_running_revenue_share (member_running_revenue_share_status);

create index IDX_succeeding_id
    on member_running_revenue_share (member_running_revenue_share_succeeding_id);

create index member_running_revenue_share_member_id
    on member_running_revenue_share (member_running_revenue_share_member_id);

create table sub_transaction
(
    subtransaction_id                         bigint unsigned auto_increment
        primary key,
    subtransaction_transaction_id             bigint unsigned                      not null,
    subtransaction_customer_product_id        bigint unsigned                      null,
    subtransaction_type                       smallint unsigned                    not null,
    subtransaction_amount                     decimal(65, 10) default 0.0000000000 not null,
    subtransaction_fees                       json                                 not null comment '(DC2Type:json)',
    subtransaction_details                    json                                 null comment '(DC2Type:json)',
    subtransaction_dwl_id                     bigint as (json_extract(`subtransaction_details`, _utf8mb3'$.dwl.id')),
    subtransaction_dwl_turnover               decimal(65, 10) as (json_extract(`subtransaction_details`, _utf8mb3'$.dwl.turnover')),
    subtransaction_dwl_winloss                decimal(65, 10) as (json_extract(`subtransaction_details`, _utf8mb3'$.dwl.winLoss')),
    subtransaction_virtual_immutable_username varchar(255) as (json_extract(`subtransaction_details`,
        _utf8mb3'$.immutableCustomerProductData.username')),
    constraint FK_97CE704F1F028B15
        foreign key (subtransaction_transaction_id) references transaction (transaction_id),
    constraint FK_97CE704F4464C11B
        foreign key (subtransaction_customer_product_id) references customer_product (cproduct_id)
)
    collate = utf8_unicode_ci;

create index IDX_97CE704F1F028B15
    on sub_transaction (subtransaction_transaction_id);

create index IDX_97CE704F4464C11B
    on sub_transaction (subtransaction_customer_product_id);

create index dwl_id_index
    on sub_transaction (subtransaction_dwl_id);

create index dwl_turnover_index
    on sub_transaction (subtransaction_dwl_turnover);

create index dwl_winloss_index
    on sub_transaction (subtransaction_dwl_winloss);

create index immutable_username_index
    on sub_transaction (subtransaction_virtual_immutable_username);

create index type_index
    on sub_transaction (subtransaction_type);

create index IDX_723705D131849CE7
    on transaction (transaction_created_by);

create index IDX_723705D1682A26E5
    on transaction (transaction_payment_option_on_transaction_id);

create index IDX_723705D184AD945B
    on transaction (transaction_currency_id);

create index IDX_723705D1ABCAC298
    on transaction (transaction_payment_option_id);

create index IDX_723705D1B8E9B9B1
    on transaction (transaction_gateway_id);

create index bet_event_id_index
    on transaction (transaction_bet_event_id);

create index bet_id_index
    on transaction (transaction_bet_id);

create index bitcoin_confirmation
    on transaction (transaction_bitcoin_confirmation);

create index bitcoin_confirmation_count_index
    on transaction (transaction_bitcoin_confirmation_count);

create index bitcoin_is_acknowledge_by_member
    on transaction (transaction_virtual_bitcoin_is_acknowledge_by_member);

create index commission_computed_original_index
    on transaction (transaction_commission_computed_original);

create index createdAt_index
    on transaction (transaction_created_at);

create index customer_index
    on transaction (transaction_customer_id);

create index date_index
    on transaction (transaction_date);

create index dwlId_index
    on transaction (dwl_id);

create index finishedAt_index
    on transaction (transaction_finished_at);

create index isVoided_index
    on transaction (transaction_is_voided);

create index number_index
    on transaction (transaction_number);

create index status
    on transaction (transaction_status);

create index toCustomer_index
    on transaction (transaction_to_customer);

create index transaction_payment_option_type_idx
    on transaction (transaction_payment_option_type);

create index type_index
    on transaction (transaction_type);

create index updatedAt_index
    on transaction (transaction_updated_at);

create index virtual_bitcoin_transaction_hash_index
    on transaction (transaction_virtual_bitcoin_transaction_hash);

create index IDX_8D93D6491ED93D47
    on user (user_group_id);

create index IDX_8D93D64979756DBA
    on user (user_created_by);

create index email_index
    on user (user_email);

create index username_index
    on user (user_username);

create index usertype_index
    on user (user_type);

create table winloss
(
    winloss_id              bigint unsigned auto_increment
        primary key,
    winloss_affiliate       bigint unsigned                      null,
    winloss_created_at      datetime                             not null comment '(DC2Type:datetime)',
    winloss_created_by      bigint unsigned                      not null,
    winloss_date            date                                 not null,
    winloss_member          bigint unsigned                      null,
    winloss_payout          decimal(65, 10) default 0.0000000000 not null,
    winloss_period          bigint unsigned                      not null,
    winloss_pin_user_code   varchar(255)                         not null,
    winloss_product         bigint unsigned                      null,
    winloss_status          tinyint(1)                           not null,
    winloss_turnover        decimal(65, 10) default 0.0000000000 not null,
    winloss_updated_at      datetime                             not null comment '(DC2Type:datetime)',
    winloss_updated_by      bigint                               not null,
    winloss_pregenerated_at datetime                             null,
    constraint winloss_date_winloss_pin_user_code_winloss_status
        unique (winloss_date, winloss_pin_user_code, winloss_status)
)
    collate = utf8_unicode_ci;

create
definer = piwidb_user@`%` procedure sp_ewl_addupdate_accountdata(IN accountData json)
BEGIN

DECLARE
EXIT HANDLER FOR SQLEXCEPTION
BEGIN
GET DIAGNOSTICS CONDITION 1
    @P1= RETURNED_SQLSTATE, @P2= MESSAGE_TEXT;
set
@idData = JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.id'));
set
@creationDateData=if(JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.entryDate'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.entryDate')));

set
@uniqueIdData=CONCAT(@idData, '-',@creationDateData);
insert into ewl_account_export_data_error(ErrorText, ErrorSQLState, SourceId)
values (@P2, @P1, @uniqueIdData);

End;

set
@id = JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.id'));
set
@customerId=if(JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.customer_id'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.customer_id')));
set
@entryDate=if(JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.entry_date'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.entry_date')));
set
@walletId=if(JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.wallet_id'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.wallet_id')));
set
@debit=if(JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.debit'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.debit')));
set
@credit=if(JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.credit'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.credit')));
set
@beforeBalance=if(JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.before_balance'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.before_balance')));
set
@afterBalance=if(JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.after_balance'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.after_balance')));
set
@currency=if(JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.currency'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.currency')));
set
@currencyExRate=if(JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.currency_ex_rate'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.currency_ex_rate')));
set
@origin=if(JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.origin'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.origin')));
set
@originId=if(JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.origin_id'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.origin_id')));
set
@descriptionData=if(JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.description'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.description')));
set
@commissionRate=if(JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.commission_rate'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(accountData, '$.commission_rate')));
set
@uniqueId=CONCAT(@id, '-',@entryDate);


INSERT INTO ewl_account_export_data
(`unique_id`, `id`, `customer_id`, `entry_date`, `wallet_id`, `debit`, `credit`, `before_balance`, `after_balance`,
 `currency`, `currency_ex_rate`, `origin`, `origin_id`, `description`, `commission_rate`)
VALUES (@uniqueId, @id, @customerId, @entryDate, @walletId, @debit, @credit, @beforeBalance, @afterBalance,
        @currency, @currencyExRate, @origin, @originId, @descriptionData, @commissionRate);


CALL `sp_load_ewl_account_export_data`(@uniqueId);

END;

create
definer = piwidb_user@`%` procedure sp_ewl_addupdate_offerdata(IN offerData json)
BEGIN

DECLARE
EXIT HANDLER FOR SQLEXCEPTION
BEGIN
GET DIAGNOSTICS CONDITION 1
    @P1= RETURNED_SQLSTATE, @P2= MESSAGE_TEXT;
set
@idData = JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.id'));
set
@creationDateData=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.creation_date'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.creation_date')));

set
@uniqueIdData=CONCAT(@idData, '-',@creationDateData);
insert into ewl_offer_export_data_error(ErrorText, ErrorSQLState, SourceId)
values (@P2, @P1, @uniqueIdData);

End;

set
@id = JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.id'));
set
@size=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.size'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.size')));
set
@price=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.price'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.price')));
set
@side=cast(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.side')) as signed int);
set
@state=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.state'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.state')));
set
@profit=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.profit'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.profit')));
set
@resettled=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.resettled'))='null',null,cast(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.resettled'))as signed int));
set
@customerId=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.customer_id'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.customer_id')));
set
@betId=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.bet_id'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.bet_id')));
set
@creationDate=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.creation_date'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.creation_date')));
set
@placedDate=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.placed_date'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.placed_date')));
set
@lastModified=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.last_modified'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.last_modified')));
set
@walletId=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.wallet_id'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.wallet_id')));
set
@selectionId=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.selection_id'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.selection_id')));
set
@marketId=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.market_id'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.market_id')));
set
@eventId=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.event_id'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.event_id')));
set
@eventTypeId=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.event_type_id'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.event_type_id')));
set
@competitionName=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.competition_name'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.competition_name')));
set
@country=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.country'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.country')));
set
@eventTypeName=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.event_type_name'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.event_type_name')));
set
@eventName=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.event_name'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.event_name')));
set
@marketName=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.market_name'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.market_name')));
set
@selectionName=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.selection_name'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.selection_name')));
set
@virtualSize=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.virtual_size'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.virtual_size')));
set
@matchedDate=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.match_date'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.match_date')));
set
@avgPriceMatched=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.avg_price_matched'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.avg_price_matched')));
set
@sizeMatched=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.size_matched'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.size_matched')));
set
@virtualSizeMatched=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.virtual_size_matched'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.virtual_size_matched')));
set
@sizeRemaining=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.size_remaining'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.size_remaining')));
set
@virtualSizeRemaining=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.virtual_size_remaining'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.virtual_size_remaining')));
set
@sizeLapsed=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.size_lapsed'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.size_lapsed')));
set
@virtualSizeLapsed=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.virtual_size_lapsed'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.virtual_size_lapsed')));
set
@sizeCancelled=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.size_cancelled'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.size_cancelled')));
set
@virtualSizeCancelled=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.virtual_size_cancelled'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.virtual_size_cancelled')));
set
@sizeVoided=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.size_voided'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.size_voided')));
set
@virtualSizeVoided=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.virtual_size_voided'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.virtual_size_voided')));
set
@walletCurrency=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.wallet_currency'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.wallet_currency')));
set
@virtualProfit=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.virtual_profit'))='null',0,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.virtual_profit')));
set
@cancelledDate=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.cancelled_date'))='null',0,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.cancelled_date')));
set
@settledDate=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.settled_date'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.settled_date')));
set
@currencyExRate=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.currency_ex_rate'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.currency_ex_rate')));
set
@currencyRate=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.currency_rate'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.currency_rate')));
set
@currencyMargin=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.currency_margin'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.currency_margin')));
set
@inPlay=cast(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.in_play')) as signed int);
set
@persistenceType= if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.persistence_type'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.persistence_type')));
set
@bettingType=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.betting_type'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.betting_type')));
set
@handicap=JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.handicap'));
set
@marketStartTime=JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.market_start_time'));
set
@marketType=JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.market_type'));
set
@numberOfWinners=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.number_of_winners'))='null',0,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.number_of_winners')));
set
@eachWayDivisor=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.each_way_divisor'))='null',0,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.each_way_divisor')));
set
@cashOut=cast(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.cash_out'))as signed int);
set
@cancelledByOperator=cast(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.cancelled_by_operator'))as signed int);
set
@dataChannel=if(JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.channel'))='null',null,JSON_UNQUOTE(JSON_EXTRACT(offerData, '$.channel')));
set
@uniqueId=CONCAT(@id, '-',@creationDate);



INSERT INTO ewl_offer_export_data
(`unique_id`, `id`, `size`, `price`, `side`, `state`, `profit`, `resettled`, `customer_id`, `bet_id`, `creation_date`,
 `placed_date`, `last_modified`, `wallet_id`, `selection_id`, `market_id`, `event_id`, `event_type_id`,
 `competition_name`,
 `country`, `event_type_name`, `event_name`, `market_name`, `selection_name`, `virtual_size`, `matched_date`,
 `avg_price_matched`, `size_matched`, `virtual_size_matched`, `size_remaining`, `virtual_size_remaining`,
 `size_lapsed`, `virtual_size_lapsed`, `size_cancelled`, `virtual_size_cancelled`, `size_voided`, `virtual_size_voided`,
 `wallet_currency`, `virtual_profit`, `cancelled_date`, `settled_date`, `currency_ex_rate`,
 `currency_rate`, `currency_margin`, `in_play`, `persistence_type`, `betting_type`, `handicap`, `market_start_time`,
 `market_type`, `number_of_winners`, `each_way_divisor`, `cash_out`, `cancelled_by_operator`,
 `channel`)
VALUES (@uniqueId, @id, @size, @price, @side, @state, @profit, @resettled, @customerId, @betId, @creationDate,
        @placedDate, @lastModified, @walletId, @selectionId,
        @marketId, @eventId, @eventTypeId, @competitionName, @country, @eventTypeName, @eventName, @marketName,
        @selectionName, @virtualSize, @matchedDate,
        @avgPriceMatched, @sizeMatched, @virtualSizeMatched, @sizeRemaining, @virtualSizeRemaining, @sizeLapsed,
        @virtualSizeLapsed, @sizeCancelled, @virtualSizeCancelled, @sizeVoided, @virtualSizeVoided, @walletCurrency,
        @virtualProfit, IFNULL(@cancelledDate, 0), @settledDate, @currencyExRate, @currencyRate, @currencyMargin,
        @inPlay,
        @persistenceType, @bettingType, @handicap, @marketStartTime, @marketType, @numberOfWinners, @eachWayDivisor,
        @cashOut, @cancelledByOperator, @dataChannel);

CALL `sp_load_ewl_offer_export_data`(@uniqueId);


END;

create
definer = piwidb_user@`%` procedure sp_ewl_member_pnl_pregen()
BEGIN
	DECLARE finished INTEGER DEFAULT 0;
    DECLARE betYear INTEGER DEFAULT 0;
    DECLARE memberId INTEGER DEFAULT 0;
    DECLARE customerId VARCHAR(100) DEFAULT '';
    DECLARE betDate DATE;
    DECLARE countData DOUBLE;

    DEClARE curReport
		CURSOR FOR
SELECT DISTINCT bet_year, bet_date,
                cp.cproduct_customer_id AS member_id, emp.customer_id
FROM ewl_member_pnl emp
         INNER JOIN customer_product cp
                    ON cp.cproduct_username = emp.customer_id
WHERE (emp.pregenerated_at IS NULL)
   OR (emp.pregenerated_at < emp.updated_at);

DECLARE CONTINUE HANDLER
        FOR NOT FOUND SET finished = 1;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
BEGIN
GET DIAGNOSTICS CONDITION 1
    @sqlState=RETURNED_SQLSTATE, @errorMessage=MESSAGE_TEXT;

INSERT INTO member_pnl_report_pregen_error(ErrorText,ErrorSQLState,Source, CreatedDate)
VALUES (@errorMessage,@sqlState,'sp_ewl_member_pnl_pregen',sysdate());
END;

OPEN curReport;

getReport: LOOP
		FETCH curReport INTO betYear, betDate, memberId, customerId;

		IF finished = 1 THEN
			LEAVE getReport;
END IF;

		SET @betYear:=betYear;
        SET @betDate:=betDate;
        SET @memberId:=memberId;
        SET @customerId:=customerId;

SELECT COUNT(*) INTO @countData FROM member_pnl_report WHERE bet_year = @betYear AND bet_date = @betDate AND member_id = @memberId;

IF @countData > 0 THEN
UPDATE member_pnl_report AS m
    INNER JOIN (SELECT p.bet_year, p.bet_date,
    COUNT(*) AS total_bets,
    SUM(p.virtual_size_matched) AS total_turnover,
    SUM(ifnull(p.profit,0)) AS total_pnl,
    SUM(p1.total_commission) AS total_commission
    FROM ewl_member_pnl p
    INNER JOIN (SELECT p2.bet_year, p2.bet_date, p2.customer_id,
    SUM(CASE WHEN p2.total_profit > 0 AND ifnull(c.commission_rate,0) <> 0
    THEN (ROUND(p2.total_profit * (c.commission_rate / 100), 2)) ELSE 0 END) AS total_commission
    FROM (SELECT bet_year, bet_date, customer_id, market_id,
    SUM(CASE WHEN profit > 0 THEN profit ELSE 0 END) AS total_profit
    FROM ewl_member_pnl
    WHERE bet_year = @betYear
    AND bet_date = @betDate
    AND customer_id = @customerId
    GROUP BY bet_year, bet_date, customer_id, market_id) p2
    LEFT OUTER JOIN ewl_commission_charges c
    ON c.customer_id = p2.customer_id
    AND SUBSTRING_INDEX(c.market_id, '|', 1) = p2.market_id
    GROUP BY p2.bet_year, p2.bet_date, p2.customer_id
    ) p1
    ON p1.bet_year = p.bet_year
    AND p1.bet_date = p.bet_date
    AND p1.customer_id = p.customer_id
    WHERE p.bet_year = @betYear
    AND p.bet_date = @betDate
    AND p.customer_id = @customerId
    GROUP BY p.bet_year, p.bet_date
    ) AS a ON m.bet_year = a.bet_year AND m.bet_date = a.bet_date AND m.member_id = @memberId
    SET m.ewl_total_bets = a.total_bets,
        m.ewl_total_turnover = a.total_turnover,
        m.ewl_total_pnl = a.total_pnl,
        m.ewl_total_commission = a.total_commission;
ELSE
	INSERT INTO member_pnl_report (bet_year, bet_date, member_id, ewl_total_bets, ewl_total_turnover, ewl_total_pnl, ewl_total_commission)
SELECT p.bet_year, p.bet_date, @memberId,
       COUNT(*) AS total_bets,
       SUM(p.virtual_size_matched) AS total_turnover,
       SUM(ifnull(p.profit,0)) AS total_pnl,
       SUM(p1.total_commission) AS total_commission
FROM ewl_member_pnl p
         INNER JOIN (SELECT p2.bet_year, p2.bet_date, p2.customer_id,
                            SUM(CASE WHEN p2.total_profit > 0 AND ifnull(c.commission_rate,0) <> 0
                                         THEN (ROUND(p2.total_profit * (c.commission_rate / 100), 2)) ELSE 0 END) AS total_commission
                     FROM (SELECT bet_year, bet_date, customer_id, market_id,
                                  SUM(CASE WHEN profit > 0 THEN profit ELSE 0 END) AS total_profit
                           FROM ewl_member_pnl
                           WHERE bet_year = @betYear
                             AND bet_date = @betDate
                             AND customer_id = @customerId
                           GROUP BY bet_year, bet_date, customer_id, market_id) p2
                              LEFT OUTER JOIN ewl_commission_charges c
                                              ON c.customer_id = p2.customer_id
                                                  AND SUBSTRING_INDEX(c.market_id, '|', 1) = p2.market_id
                     GROUP BY p2.bet_year, p2.bet_date, p2.customer_id
) p1
                    ON p1.bet_year = p.bet_year
                        AND p1.bet_date = p.bet_date
                        AND p1.customer_id = p.customer_id
WHERE p.bet_year = @betYear
  AND p.bet_date = @betDate
  AND p.customer_id = @customerId
GROUP BY p.bet_year, p.bet_date;
END IF;

UPDATE ewl_member_pnl
SET pregenerated_at = SYSDATE()
WHERE bet_year = @betYear
  AND bet_date = @betDate
  AND customer_id = @customerId;

END LOOP getReport;

CLOSE curReport;


END;

create
definer = piwidb_user@`%` procedure sp_fix_data()
BEGIN

    DECLARE uniqueID VARCHAR(500);
    DECLARE done TINYINT DEFAULT FALSE;
    DECLARE cursor1
        CURSOR FOR
SELECT emp.unique_id FROM ewl_member_pnl emp;

DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

OPEN cursor1;

my_loop:
    LOOP
        FETCH NEXT FROM cursor1 INTO uniqueID;
        IF done THEN
            LEAVE my_loop;
ELSE
            CALL `sp_load_ewl_offer_export_data`(uniqueID);
END IF;
END LOOP;

CLOSE cursor1;

CALL `sp_member_pnl_report_pregen`();
END;

create
definer = piwidb_user@`%` procedure sp_load_ewl_account_export_data(IN uniqueId varchar(500))
BEGIN
	DECLARE
customerId VARCHAR(100);
    DECLARE
accountOrigin varchar(100);
    DECLARE
originId VARCHAR(50);
    DECLARE
commissionRate DOUBLE;
    DECLARE
exportId BIGINT;

	SET
@uniqueId:=uniqueId;

SELECT id, customer_id, origin, origin_id, commission_rate
INTO @exportId, @customerId, @accountOrigin, @originId, @commissionRate
FROM ewl_account_export_data
WHERE unique_id = @uniqueId LIMIT 1;

IF
@accountOrigin = 'CHARGE_COMMISSION' THEN

        IF ( SELECT EXISTS (
			SELECT 1
			FROM ewl_commission_charges
			WHERE account_export_id = @exportId
		) ) THEN
UPDATE ewl_commission_charges
SET customer_id     = @customerId,
    market_id       = @originId,
    commission_rate = @commissionRate
WHERE account_export_id = @exportId;
ELSE
			INSERT INTO ewl_commission_charges (account_export_id, customer_id, market_id, commission_rate)
				VALUES (@exportId, @customerId, @originId, @commissionRate);
END IF;
END IF;

END;

create
definer = piwidb_user@`%` procedure sp_load_ewl_offer_export_data(IN uniqueId varchar(500))
BEGIN
    SET @unique_id=uniqueId;

	IF ( SELECT EXISTS (
                                SELECT 1
                                FROM ewl_member_pnl
                                WHERE unique_id = @unique_id
                            ) ) THEN
UPDATE ewl_member_pnl AS t
    INNER JOIN (SELECT
    unique_id,
    bet_id,
    customer_id,
    market_id,
    from_unixtime(placed_date/1000) AS placed_date,
    from_unixtime(last_modified/1000) AS last_modified,
    event_type_name,
    competition_name,
    event_name,
    from_unixtime(matched_date/1000) AS matched_date,
    from_unixtime(cancelled_date/1000) AS cancelled_date,
    from_unixtime(settled_date/1000) AS settled_date,
    selection_name,
    price,
    side,
    state,
    virtual_size_matched,
    virtual_size_remaining,
    virtual_profit
    FROM ewl_offer_export_data
    WHERE unique_id=@unique_id
    AND settled_date IS NOT NULL
    AND state IN (4,5)
    ORDER BY last_modified DESC
    LIMIT 1
    ) AS s
ON t.unique_id = s.unique_id
    SET t.bet_id = s.bet_id,
        t.customer_id = s.customer_id,
        t.market_id = s.market_id,
        t.placed_date = s.placed_date,
        t.last_modified = s.last_modified,
        t.event_type_name = s.event_type_name,
        t.competition_name = s.competition_name,
        t.event_name = s.event_name,
        t.matched_date = s.matched_date,
        t.cancelled_date = s.cancelled_date,
        t.settled_date = s.settled_date,
        t.selection_name = s.selection_name,
        t.price = s.price,
        t.side = s.side,
        t.state = s.state,
        t.virtual_size_matched = s.virtual_size_matched,
        t.virtual_size_remaining = s.virtual_size_remaining,
        t.profit = s.virtual_profit,
        t.updated_at = now();
ELSE
		INSERT INTO ewl_member_pnl (unique_id,bet_year, bet_date,bet_id,customer_id,market_id,creation_date,placed_date,last_modified,
			event_type_name,competition_name,event_name,matched_date,cancelled_date,settled_date,selection_name,price,side,state,virtual_size_matched,
			virtual_size_remaining,profit,created_at,updated_at,pregenerated_at)
SELECT
    unique_id,
    YEAR(from_unixtime(settled_date/1000)) AS bet_year,
    DATE(from_unixtime(settled_date/1000)) AS bet_date,
    bet_id,
    customer_id,
    market_id,
    from_unixtime(creation_date/1000) AS creation_date,
    from_unixtime(placed_date/1000) AS placed_date,
    from_unixtime(last_modified/1000) AS last_modified,
    event_type_name,
    competition_name,
    event_name,
    from_unixtime(matched_date/1000) AS matched_date,
    from_unixtime(cancelled_date/1000) AS cancelled_date,
    from_unixtime(settled_date/1000) AS settled_date,
    selection_name,
    price,
    side,
    state,
    virtual_size_matched,
    virtual_size_remaining,
    virtual_profit,
    now() AS created_at,
    null AS updated_at,
    null AS pregenerated_at
FROM ewl_offer_export_data
WHERE unique_id=@unique_id
  AND settled_date IS NOT NULL
  AND state IN (4,5)
ORDER BY last_modified DESC
    LIMIT 1;
END IF;

END;

create
definer = piwidb_user@`%` procedure sp_member_pnl_report(IN pageNumber int, IN pageSize int, IN recordCount int,
                                                             IN startDate char(10), IN endDate char(10),
                                                             IN userName varchar(255), IN isActive tinyint)
BEGIN
    DECLARE offsetData INT;
    DECLARE countData DOUBLE;

    SET @startYear = YEAR(startDate);
    SET @endYear = YEAR(endDate);

    SET @whereData:='';

    SET @limitData = pageSize;
    IF (pageNumber=1) THEN
        SET @offsetData:=0;
ELSE
        SET @offsetData=((pageSize * pageNumber)-(pageSize));
END IF;

    SET @countData:=recordCount;

    SET @reportQuery:= '
		SELECT
        1 AS row_count,
        u.user_username,
        IF(u.user_is_active = 1, "Active", "Inactive") AS user_is_active,
		ROUND(SUM(m.ewl_total_bets),2) AS ewl_total_bets,
		ROUND(SUM(m.ewl_total_turnover),2) AS ewl_total_turnover,
		ROUND(SUM(m.ewl_total_pnl),2) AS ewl_gross_pnl,
		ROUND(SUM(m.ewl_total_commission),2) AS ewl_total_commission,
		ROUND(SUM(ROUND(m.ewl_total_pnl,2) - ROUND(ewl_total_commission,2)),2) AS ewl_net_pnl,
		ROUND(SUM(m.pin_total_turnover),2) AS pin_total_turnover,
		ROUND(SUM(m.pin_total_pnl),2) AS pin_total_pnl,
		ROUND(SUM(m.evo_total_wager),2) AS evo_total_wager,
		ROUND(SUM(m.evo_total_pnl),2) AS evo_total_pnl
	FROM member_pnl_report m
    INNER JOIN customer c ON c.customer_id = m.member_id
    INNER JOIN user u ON u.user_id = c.customer_user_id
	WHERE m.bet_year BETWEEN [startYear] AND [endYear]
	AND m.bet_date BETWEEN ''[startDate]'' AND ''[endDate]''
    [whereData]
    GROUP BY u.user_username, u.user_is_active
    ';

    SET @reportQuery = REPLACE(@reportQuery,'[startYear]',@startYear);
    SET @reportQuery = REPLACE(@reportQuery,'[endYear]',@endYear);
    SET @reportQuery = REPLACE(@reportQuery,'[startDate]',startDate);
    SET @reportQuery = REPLACE(@reportQuery,'[endDate]',endDate);

    IF IFNULL(userName,'') <> '' THEN
        SET @whereData:= CONCAT(@whereData, ' AND (u.user_username LIKE ''%',userName,'%''', 'OR c.customer_id IN (SELECT cp.cproduct_customer_id FROM customer_product cp WHERE cp.cproduct_username LIKE ''%',userName, '%''))');
END IF;

    IF isActive <> -1 THEN
        SET @whereData:= CONCAT(@whereData, ' AND u.user_is_active = ',isActive);
END IF;

    SET @reportQuery = REPLACE(@reportQuery,'[whereData]',@whereData);

    IF @countData = -1 THEN
        SET @countQuery:= CONCAT('SELECT COUNT(*) INTO @countData FROM (',@reportQuery,') a');
PREPARE stmtCount FROM @countQuery;
EXECUTE stmtCount;
DEALLOCATE PREPARE stmtCount;
END IF;

    SET @countStmt = CONCAT(@countData, ' AS row_count');
    SET @reportQuery = REPLACE(@reportQuery,'1 AS row_count',@countStmt);

    IF pageSize > 0 THEN
        SET @reportQuery = CONCAT(@reportQuery, ' ORDER BY u.user_username LIMIT ? OFFSET ?');

PREPARE stmtMain FROM @reportQuery;
EXECUTE stmtMain USING @limitData, @offsetData;
ELSE

        SET @mainDBQuery = CONCAT(@reportQuery, ' ORDER BY u.user_username');
PREPARE stmtMain FROM @reportQuery;
EXECUTE stmtMain;
END IF;

DEALLOCATE PREPARE stmtMain;

END;

create
definer = piwidb_user@`%` procedure sp_member_pnl_report_pregen()
BEGIN
CALL `sp_winloss_pregen`();
CALL `sp_ewl_member_pnl_pregen`();
END;

create
definer = piwidb_user@`%` procedure sp_winloss_pregen()
BEGIN
	DECLARE finished INTEGER DEFAULT 0;
    DECLARE betYear INTEGER DEFAULT 0;
    DECLARE memberId INTEGER DEFAULT 0;
    DECLARE productId BIGINT DEFAULT 0;
    DECLARE betDate DATE;
    DECLARE countData DOUBLE;

    DEClARE curReport
		CURSOR FOR
SELECT DISTINCT YEAR(w.winloss_date) AS bet_year, w.winloss_date, w.winloss_member,
    w.winloss_product
FROM winloss w
WHERE (w.winloss_pregenerated_at IS NULL)
   OR (w.winloss_pregenerated_at < winloss_updated_at);

DECLARE CONTINUE HANDLER
        FOR NOT FOUND SET finished = 1;

	DECLARE EXIT HANDLER FOR SQLEXCEPTION
BEGIN
GET DIAGNOSTICS CONDITION 1
    @sqlState=RETURNED_SQLSTATE, @errorMessage=MESSAGE_TEXT;

INSERT INTO member_pnl_report_pregen_error(ErrorText,ErrorSQLState,Source, CreatedDate)
VALUES (@errorMessage,@sqlState,'sp_winloss_pregen',sysdate());
END;

OPEN curReport;

getReport: LOOP
		FETCH curReport INTO betYear, betDate, memberId, productId;
		IF finished = 1 THEN
			LEAVE getReport;
END IF;

SELECT COUNT(*) INTO @countData FROM member_pnl_report WHERE bet_year = betYear AND bet_date = betDate AND member_id = memberId;

IF @countData > 0 THEN
UPDATE member_pnl_report AS m
    INNER JOIN (SELECT YEAR(winloss_date) AS bet_year, winloss_date AS bet_date, winloss_member AS member_id,
    SUM(winloss_turnover) AS pin_total_turnover,
    SUM(winloss_payout) AS pin_total_pnl
    FROM winloss
    WHERE winloss_date = betDate
    AND winloss_member = memberId
    GROUP BY YEAR(winloss_date), winloss_date, winloss_member
    ) AS a ON m.bet_year = a.bet_year AND m.bet_date = a.bet_date AND m.member_id = a.member_id
    SET m.pin_total_turnover = a.pin_total_turnover,
        m.pin_total_pnl = a.pin_total_pnl;
ELSE
			INSERT INTO member_pnl_report (bet_year, bet_date, member_id, pin_total_turnover, pin_total_pnl)
SELECT YEAR(winloss_date), winloss_date, winloss_member,
    SUM(winloss_turnover) AS pin_total_turnover,
    SUM(winloss_payout) AS pin_total_pnl
FROM winloss
WHERE winloss_date = betDate
  AND winloss_member = memberId
GROUP BY YEAR(winloss_date), winloss_date, winloss_member;
END IF;

UPDATE winloss
SET winloss_pregenerated_at = SYSDATE()
WHERE winloss_date = betDate
  AND winloss_product = productId
  AND winloss_member = memberId;

END LOOP getReport;

CLOSE curReport;


END;

INSERT INTO `user` (`user_id`, `user_created_by`, `user_group_id`, `user_username`, `user_password`, `user_email`,
                    `user_phone_number`, `user_type`, `user_signup_type`, `user_is_active`, `user_roles`,
                    `user_deleted_at`, `user_zendesk_id`, `user_key`, `user_activation_code`,
                    `user_activation_sent_timestamp`, `user_activation_timestamp`, `user_created_at`, `user_updated_by`,
                    `user_updated_at`, `user_preferences`, `user_reset_password_code`,
                    `user_reset_password_sent_timestamp`)
VALUES (1, NULL, NULL, 'admin', '$2y$12$RyTY5zbg0F1YxynNzA8qR.ZlZqh2TSTjUM5Wug2RVxZgVlQPCQdvu', 'admin@localhost.com',
        NULL, 2, 2, 1, '{\"ROLE_ADMIN\": 2, \"ROLE_SUPER_ADMIN\": 2}', NULL, NULL, NULL, NULL, NULL, NULL, NOW(), NULL,
        NOW(), '{\"lastLoginIP\": \"192.168.48.1\"}', NULL, NULL);
INSERT INTO `product` (`product_id`, `product_code`, `product_name`, `product_is_active`, `product_logo_uri`,
                       `product_url`, `product_created_by`, `product_created_at`, `product_updated_by`,
                       `product_updated_at`, `product_deleted_at`, `product_details`)
VALUES (1, 'PINBET', 'Sports', 1, NULL, NULL, 1, '2022-01-10 06:50:07', NULL, NULL, NULL, '{}'),
       (2, 'PW', 'AFFILIATE PROFIT', 1, NULL, NULL, 1, '2022-01-10 06:50:08', NULL, NULL, NULL,
        '{\"piwi_wallet\": \"TRUE\"}'),
       (3, 'EVOLUTION', 'Casino', 1, NULL, NULL, 1, '2020-02-07 06:30:38', NULL, NULL, NULL, '{}'),
       (4, 'PWM', 'Member Wallet', 1, NULL, NULL, 1, '2020-02-07 06:30:38', NULL, NULL, NULL, '{}'),
       (5, 'PIWIX', 'PIWIXchange', 1, NULL, NULL, 1, '2020-02-07 06:30:38', NULL, NULL, NULL, '{}');

insert into oauth2_client (id, random_id, redirect_uris, secret, allowed_grant_types)
values (1, '4bisobliwqgw0w08s0kw0swog8w4co4os0kk04cw8s08000scs', 'a:0:{}',
        '2wfdnjv550isosc0ksscwg8o0gcgg8kkcg40go8g044so0sk0o', 'a:2:{i:0;s:8:"password";i:1;s:13:"refresh_token";}');

insert into oauth2_access_token (id, client_id, user_id, token, expires_at, scope, ip_address)
values (1, 1, 1, 'MDgzNjVhN2E0MjY2NjcyZTE0M2E1NzIwMGI3OGFmN2RjNmVhYmQ4OTE3M2I4NjEyNGE0MDhjMTM4OTgwNmUzMg', null, 'api',
        '*');


insert into payment_options (id, created_at, updated_at, code, name, provider, enabled, settings)
values (33, '2021-11-24 06:11:07', '2022-01-11 09:08:55', 'BITCOIN', 'Bitcoin Account', 'Blockchain', 1,
        '{"fields": [{"code": "account_id", "name": "Receiver Address", "type": "text", "error": [], "unique": true, "required": true}, {"code": "email", "name": "Email Address", "type": "text", "error": [], "unique": false, "required": false}, {"code": "notes", "name": "Notes", "type": "text", "error": [], "unique": false, "required": false}], "amounts": [{"rate": {"deposit": [{"to": "0.0499999999", "from": "0.0000000000", "updatedAt": "2021-12-03 12:44:43", "updatedBy": "Lalainetester", "fixedAmount": "15", "lastUpdatedAt": null, "lastUpdatedBy": null}, {"to": "0.9999999999", "from": "0.05", "updatedAt": "2021-12-03 12:06:09", "updatedBy": "LaraTester", "fixedAmount": "10", "lastUpdatedAt": null, "lastUpdatedBy": null}, {"to": "50", "from": "1", "updatedAt": "2021-12-03 12:42:52", "updatedBy": "Lalainetester", "fixedAmount": "5", "lastUpdatedAt": null, "lastUpdatedBy": null}], "withdrawal": [{"to": "0.0499999999", "from": "0.0000000000", "updatedAt": "2021-12-03 12:10:53", "updatedBy": "sarahTester", "fixedAmount": "15", "lastUpdatedAt": null, "lastUpdatedBy": null}, {"to": "0.9999999999", "from": "0.05", "updatedAt": "2021-12-03 12:10:53", "updatedBy": "sarahTester", "fixedAmount": "10", "lastUpdatedAt": null, "lastUpdatedBy": null}, {"to": "40", "from": "1", "updatedAt": "2021-12-03 12:10:53", "updatedBy": "sarahTester", "fixedAmount": "5", "lastUpdatedAt": null, "lastUpdatedBy": null}]}, "limits": {"maximumAmountDeposit": "50", "minimumAmountDeposit": "0.00005", "maximumAmountWithdrawal": "40", "minimumAmountWithdrawal": "0.000005"}, "currency": "EUR"}], "bundles": [{"data": [{"name": "processing_time", "label": "Processing Time", "value": "1 Confirmation", "unique": false, "required": false}, {"name": "deposit_fee", "label": "Fee", "value": "Free", "unique": false, "required": false}, {"name": "withdrawal_fee", "label": "Fee", "value": "Free", "unique": false, "required": false}], "name": "Default Bundle", "rule": {"status": {"enabled": false, "registered": false}, "priority": 0, "firstDeposit": false, "selectedCurrency": [], "selectedCountries": []}, "tags": [], "isDefault": true}], "provider": {"xpub": "xpub6CxTX6AHPohrERncqaHgJyMrTcqEzHMxBZNrWgmp8KsJkuL87rqctxfQeVcBnxG51Ap3tV48PK8CtrPmdfSHvR36rXRwAT2KmEetqEUK58i", "apiKey": "2db87356-bfbb-412d-8403-39b669cd9a9d", "autoLock": true, "gapLimit": "5", "password": "</LkS.+sYd99>/", "walletId": "21b83c93-2ddc-44fb-a0e2-21b40fb435ea", "autoDecline": false, "lockDownPeriod": "20", "secondPassword": "</LkS.+sYd99>/", "minutesInterval": "60", "cycleCallbackUrl": "https://internal.piwi247.com/api/v1/payment-option/blockchain/notify"}, "realtime": false, "sortOrder": "1", "linkFields": false, "autoDecline": false}'),
       (34, '2021-11-24 06:39:21', '2021-12-10 11:24:55', 'SKRILL', 'Skrill Account', 'Bluepay', 1,
        '{"fields": [{"code": "email", "name": "Email", "type": "text", "error": [], "unique": true, "required": true}, {"code": "notes", "name": "Notes", "type": "text", "error": [], "unique": false, "required": false}], "amounts": [], "bundles": [{"data": [{"name": "processing_time", "label": "Processing Time", "value": "Instant", "unique": false, "required": false}, {"name": "deposit_fee", "label": "Fee", "value": "1%", "unique": false, "required": false}, {"name": "withdrawal_fee", "label": "Fee", "value": "Free", "unique": false, "required": false}, {"name": "min_deposit", "label": "Minimum Deposit", "value": "10", "unique": false, "required": false}, {"name": "max_deposit", "label": "Maximum Deposit", "value": "10000", "unique": false, "required": false}, {"name": "min_withdrawal", "label": "Minimum Withdrawal", "value": "10", "unique": false, "required": false}, {"name": "max_withdrawal", "label": "Maximum Withdrawal", "value": "10000", "unique": false, "required": false}, {"name": "email", "label": "Email", "value": "janzovasona7@gmail.com", "unique": false, "required": false}], "name": "Default Bundle", "rule": {"status": {"enabled": false, "registered": false}, "priority": 0, "firstDeposit": false, "selectedCurrency": [], "selectedCountries": []}, "tags": [], "isDefault": true}], "provider": {"gapLimit": "Lalainetester", "password": "P@ss123!!", "autoDecline": true, "minutesInterval": "1"}, "realtime": false, "sortOrder": "2", "linkFields": true, "autoDecline": true}'),
       (35, '2021-11-24 06:42:54', '2022-01-11 10:34:51', 'NETELLER', 'Neteller Account', 'Default', 1,
        '{"fields": [{"code": "email", "name": "Email", "type": "text", "error": [], "unique": true, "required": true}, {"code": "notes", "name": "Notes", "type": "text", "error": [], "unique": false, "required": false}], "amounts": [], "bundles": [{"data": [{"name": "email", "label": "Email", "value": "janzovasona7@gmail.com", "unique": false, "required": false}, {"name": "min_deposit", "label": "Min deposit amount", "value": "10", "unique": false, "required": false}, {"name": "max_deposit", "label": "Max deposit amount", "value": "10000", "unique": false, "required": false}, {"name": "min_withdrawal", "label": "Min withdrawal amount", "value": "15", "unique": false, "required": false}, {"name": "max_withdrawal", "label": "Max withdrawal amount", "value": "10000", "unique": false, "required": false}, {"name": "deposit_fee", "label": "Fee", "value": "Free", "unique": false, "required": false}, {"name": "withdrawal_fee", "label": "Fee", "value": "Free", "unique": false, "required": false}, {"name": "processing_time", "label": "Processing Time", "value": "Instant", "unique": false, "required": false}], "name": "Default Bundle", "rule": {"status": {"enabled": false, "registered": false}, "priority": 0, "firstDeposit": false, "selectedCurrency": [], "selectedCountries": []}, "tags": [], "isDefault": true}], "provider": [], "realtime": false, "sortOrder": "3", "linkFields": true, "autoDecline": false}');

insert into customer_group (customer_group_id, customer_group_name, customer_is_default, customer_group_created_by,
                            customer_group_created_at, customer_group_updated_by, customer_group_updated_at)
values (1, 'Group ALL', 1, 1, '2019-04-22 03:04:24', 2474, '2021-11-24 13:59:02'),
       (2, 'Test Group', 0, 1, '2019-07-04 03:55:21', 2474, '2021-11-24 13:59:44');

insert into setting (setting_id, setting_code, setting_value)
values (1, 'transaction.status',
        '{"1": {"label": "Requested", "start": true, "actions": {"3": {"class": "btn-success", "label": "Acknowledge", "status": 4}, "4": {"class": "btn-danger", "label": "Decline", "status": 3}}, "amsLabel": "Requested", "editDate": false, "editFees": false, "editAmount": true, "editRemark": false, "editGateway": true, "editBonusAmount": false}, "2": {"end": true, "label": "Processed", "actions": [], "amsLabel": "Processed", "editDate": false, "editFees": false, "editAmount": false, "editRemark": false, "editGateway": false, "editBonusAmount": false}, "3": {"label": "Declined", "actions": [], "decline": true, "amsLabel": "Declined", "editDate": false, "editFees": false, "editAmount": false, "editRemark": false, "editGateway": false, "editBonusAmount": false}, "4": {"label": "Acknowledged", "actions": [{"class": "btn-success", "label": "Process", "status": 2}, {"class": "btn-danger", "label": "Decline", "status": 3}], "amsLabel": "Acknowledged", "editDate": true, "editFees": false, "editAmount": true, "editRemark": true, "editGateway": true, "editBonusAmount": false}}'),
       (2, 'transaction.paymentGateway', '"customer-group"'),
       (3, 'transaction.start', '{"admin": 1, "customer": 1}'),
       (25, 'currency.base', '1'),
       (97, 'maintenance.enabled', null),
       (98, 'transaction.equations',
        '{"dwl": {"totalAmount": {"equation": "x+y", "variables": {"x": "sum_products", "y": "total_customer_fee"}}, "customerAmount": {"equation": "x+y", "variables": {"x": "sum_products", "y": "total_customer_fee"}}}, "deposit": {"totalAmount": {"equation": "x+y", "variables": {"x": "sum_products", "y": "total_customer_fee"}}, "customerAmount": {"equation": "x", "variables": {"x": "sum_products"}}}, "transfer": {"totalAmount": {"equation": "x", "variables": {"x": "sum_withdraw_products"}}, "customerAmount": {"equation": "x", "variables": {"x": "sum_withdraw_products"}}}, "withdraw": {"totalAmount": {"equation": "x+y", "variables": {"x": "sum_products", "y": "total_customer_fee"}}, "customerAmount": {"equation": "x", "variables": {"x": "sum_products"}}}, "p2p_transfer": {"totalAmount": {"equation": "x", "variables": {"x": "sum_withdraw_products"}}, "customerAmount": {"equation": "x", "variables": {"x": "sum_withdraw_products"}}}, "revenue_share": {"totalAmount": {"equation": "x+y", "variables": {"x": "sum_products", "y": "total_customer_fee"}}, "customerAmount": {"equation": "x", "variables": {"x": "sum_products"}}}}'),
       (99, 'counter', '0'),
       (100, 'transaction.list.filters',
        '{"pending": {"filters": {"voided": "", "excludeStatus": ["2", "3"]}}, "processed": {"filters": {"status": ["voided", "3", "2"]}}}'),
       (101, 'transaction.type',
        '{"start": {"dwl": 2}, "workflow": {"dwl": {"2": {"actions": [{"label": "approve", "status": 2}]}}}}'),
       (102, 'scheduler.task',
        '{"auto_decline": {"types": [1], "reason": {"1": "No deposit received in payment gateway"}, "status": 1, "autoDecline": true, "minutesInterval": 1}}'),
       (103, 'referral.tools',
        '{"links": {"AO": {"urls": {"promotion": "https://www.asianodds88.com/register-promo.aspx/", "advertisement": "https://www.asianodds88.com/register.aspx/"}, "siteId": 3}, "DE": {"urls": {"promotion": "https://de.asianconnect88.com/promotion/acwelcome400-geschaftsbedingungen/", "advertisement": "https://de.asianconnect88.com/register/"}, "siteId": 21}, "EN": {"urls": {"promotion": "https://www.asianconnect88.com/promotion/acwelcome400-terms-and-conditions/", "advertisement": "https://www.asianconnect88.com/register/"}, "siteId": 2}, "ES": {"urls": {"promotion": "https://www.asianconect88.com/promociones/", "advertisement": "https://www.asianconect88.com/registro/"}, "siteId": 22}, "FR": {"urls": {"promotion": "https://ac0188.com/inscription/", "advertisement": "https://ac0188.com/inscription/"}, "siteId": 18}}, "piwikUrlKey": "pk_kwd"}'),
       (104, 'commission',
        '{"enable": true, "payout": {"days": "5", "time": "00:00"}, "period": {"day": "1", "every": "1", "frequency": "monthly"}, "startDate": "2020-01-22", "conditions": [{"field": "active_member", "value": ">=5"}]}'),
       (106, 'origin',
        '{"origins": [{"url": "https://www.asianconnect88.com/", "code": "EN", "name": "https://www.asianconnect88.com"}, {"url": "https://asianconnect88.net/", "code": "EN", "name": "https://asianconnect88.net"}, {"url": "https://www.asianconnect888.com/", "code": "EN", "name": "https://www.asianconnect888.com"}, {"url": "https://fr.asianconnect88.com/", "code": "FR", "name": "https://fr.asianconnect88.com"}, {"url": "https://ac0808.com/", "code": "FR", "name": "https://ac0808.com"}, {"url": "https://ac0188.com/", "code": "FR", "name": "https://ac0188.com"}, {"url": "https://ac0288.com/", "code": "FR", "name": "https://ac0288.com"}, {"url": "https://de.asianconnect88.com/", "code": "DE", "name": "https://de.asianconnect88.com"}, {"url": "https://www.asianconnectde.com/", "code": "DE", "name": "https://www.asianconnectde.com"}, {"url": "https://www.asianconect88.com/", "code": "ES", "name": "https://www.asianconect88.com"}, {"url": "http://www.asianconnect08.com/", "code": "ES", "name": "http://www.asianconnect08.com"}]}'),
       (107, 'bitcoin.confirmations',
        '[{"num": 0, "label": "Pending confirmation", "transactionStatus": "1"}, {"num": 1, "label": "Confirmed", "transactionStatus": "4"}]'),
       (108, 'piwi247.session', '{"refreshSessionInterval": "720000"}'),
       (109, 'transaction.validate',
        '{"skrill": {"deposit": {"fee": 0, "max_amount": 10000, "min_amount": 10}, "withdraw": {"fee": 0, "max_amount": 10000, "min_amount": 10}}, "bitcoin": {"deposit": {"fee": 0, "max_amount": 10000, "min_amount": 0.0001}, "withdraw": {"fee": 0, "max_amount": 10000, "min_amount": "0.0001"}}, "neteller": {"deposit": {"fee": 0, "max_amount": 10000, "min_amount": 10}, "withdraw": {"fee": 0, "max_amount": 10000, "min_amount": 10}}}'),
       (110, 'bitcoin.setting.withdrawalConfiguration', '[{"min": "200"}]'),
       (113, 'bitcoin.setting',
        '{"configuration": {"maximumAllowedDeposit": "50", "minimumAllowedDeposit": "0.0020"}, "paymentOption": "BITCOIN", "lockRatePeriodSetting": {"autoLock": true, "minutesLockDownInterval": 60}, "withdrawalConfiguration": {"maximumAllowedWithdrawal": "60", "minimumAllowedWithdrawal": "0.0020"}}'),
       (114, 'pinnacle', '{"product": "PINBET", "transaction": {"deposit": {"status": 2}, "withdraw": {"status": 4}}}'),
       (128, 'session.timeout', '3600'),
       (129, 'session.pinnacle_timeout', '540'),
       (159, 'pinnacle.hotevents', '{"items": [{"name": "E Sports", "limit": "", "events": ""}]}'),
       (178, 'system.maintenance',
        '{"app": [{"key": "member", "label": "Member Site", "value": false}, {"key": "affiliate", "label": "Afiliate Site", "value": false}], "integration": [{"key": "piwix", "label": "PiwixChange", "value": false, "is_default": true}, {"key": "sports", "label": "Sports", "value": false, "is_default": false}, {"key": "casino", "label": "Casino", "value": false, "is_default": false}]}');

insert into gateway (gateway_id, gateway_payment_option, gateway_currency_id, gateway_name, gateway_balance,
                     gateway_is_active, gateway_created_by, gateway_created_at, gateway_updated_by, gateway_updated_at,
                     gateway_details, gateway_levels)
values (1, 'BITCOIN', 1, 'Blockchain BTC', 2546610.2500000000, 1, 3, '2019-05-10 05:22:18', 4480, '2022-01-11 10:41:23',
        '{"config": {"guid": "7a8adc86-7259-42f0-b9f7-9583b0ac8079", "password": "Hope++//2021", "senderXpub": "xpub6CHmNbsE86N7Hr3wmNCzz6aSz8LX6hTaasPnHqwzDHSXLp8Wuy8BwbYYL8ZAwAdfRM9ob5HSQyLsTyRKihx6mqcYrnX8GENa9VfHT5SyC7Q", "receiverXpub": "xpub6CHmNbsE86N7Hr3wmNCzz6aSz8LX6hTaasPnHqwzDHSXLp8Wuy8BwbYYL8ZAwAdfRM9ob5HSQyLsTyRKihx6mqcYrnX8GENa9VfHT5SyC7Q", "secondPassword": null}, "methods": {"deposit": {"type": "deposit", "equation": "+(a-b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "company_fee"}]}, "withdraw": {"type": "withdraw", "equation": "-(a+b)", "variables": [{"var": "a", "value": "customer_amount"}, {"var": "b", "value": "company_fee"}]}}}',
        '[]'),
       (2, 'NETELLER', 1, 'NT-Piwi 1', 128854.7700000000, 1, 10, '2019-05-15 10:28:59', 4480, '2022-01-11 10:42:44',
        '{"config": {"bankName": null, "bankHolder": null, "bankAccount": null}, "methods": {"deposit": {"type": "deposit", "equation": "+(a-b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "company_fee"}]}, "withdraw": {"type": "withdraw", "equation": "-(a+b)", "variables": [{"var": "a", "value": "customer_amount"}, {"var": "b", "value": "company_fee"}]}}}',
        '[]'),
       (3, 'SKRILL', 1, 'MB-Piwi 1', 52448.2800000000, 1, 10, '2019-05-16 13:09:33', 4480, '2022-01-11 10:26:34',
        '{"config": {"bankName": null, "bankHolder": null, "bankAccount": null}, "methods": {"deposit": {"type": "deposit", "equation": "+(a-b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "company_fee"}]}, "withdraw": {"type": "withdraw", "equation": "-(a+b)", "variables": [{"var": "a", "value": "customer_amount"}, {"var": "b", "value": "company_fee"}]}}}',
        '[]'),
       (4, 'NETELLER', 1, 'Bonus', -4477.4500000000, 1, 10, '2019-06-14 06:34:59', 27, '2021-11-17 15:12:21',
        '{"config": {"bankName": null, "bankHolder": null, "bankAccount": null}, "methods": {"deposit": {"type": "deposit", "equation": "-(a-b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "company_fee"}]}, "withdraw": {"type": "withdraw", "equation": "-(a+b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "company_fee"}]}}}',
        '[]'),
       (5, 'SKRILL', 1, 'Test Skrill', 4979284.8000000000, 1, 1, '2019-07-03 07:25:03', 2555, '2022-01-11 13:47:49',
        '{"config": {"bankName": null, "bankHolder": null, "bankAccount": null}, "methods": {"deposit": {"type": "deposit", "equation": "+(a-b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "company_fee"}]}, "withdraw": {"type": "withdraw", "equation": "-(a+b)", "variables": [{"var": "a", "value": "customer_amount"}, {"var": "b", "value": "company_fee"}]}}}',
        '[]'),
       (6, 'SKRILL', 1, 'Asianconnect888', 703527.3000000000, 1, 10, '2021-06-25 08:42:20', 2267, '2021-11-26 13:26:48',
        '{"config": {"bankName": null, "bankHolder": null, "bankAccount": null}, "methods": {"deposit": {"type": "deposit", "equation": "+(a-b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "company_fee"}]}, "withdraw": {"type": "withdraw", "equation": "-(a+b)", "variables": [{"var": "a", "value": "customer_amount"}, {"var": "b", "value": "company_fee"}]}}}',
        '[]'),
       (7, 'SKRILL', 1, 'TEST_SA', -10.0000000000, 0, 2474, '2021-06-28 10:51:48', 2474, '2021-11-23 11:22:14',
        '{"config": {"bankName": null, "bankHolder": null, "bankAccount": null}, "methods": {"deposit": {"type": "deposit", "equation": "+(a-b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "customer_fee"}]}, "withdraw": {"type": "withdraw", "equation": "-(a-b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "customer_fee"}]}}}',
        '[]'),
       (8, 'NETELLER', 1, 'TEST_LA', 10.0000000000, 0, 2555, '2021-06-29 07:48:02', 2555, '2021-11-04 08:58:20',
        '{"config": {"bankName": null, "bankHolder": null, "bankAccount": null}, "methods": {"deposit": {"type": "deposit", "equation": "+(a-b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "customer_fee"}]}, "withdraw": {"type": "withdraw", "equation": "-(a-b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "customer_fee"}]}}}',
        '[]'),
       (9, 'TEST_REG', 1, 'TEST_REG1', 20.0000000000, 0, 2474, '2021-06-29 08:30:17', 2555, '2021-08-23 07:57:39',
        '{"config": {"bankName": null, "bankHolder": null, "bankAccount": null}, "methods": {"deposit": {"type": "deposit", "equation": "+(a-b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "customer_fee"}]}, "withdraw": {"type": "withdraw", "equation": "-(a-b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "customer_fee"}]}}}',
        '[]'),
       (10, 'SKRILL', 1, 'Test SK-01', 30.0000000000, 1, 2474, '2021-11-24 13:43:55', 2267, '2021-11-26 06:10:51',
        '{"config": [], "methods": {"deposit": {"type": "deposit", "equation": "+(a-b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "company_fee"}]}, "withdraw": {"type": "withdraw", "equation": "-(a+b)", "variables": [{"var": "a", "value": "customer_amount"}, {"var": "b", "value": "company_fee"}]}}}',
        '[]'),
       (11, 'NETELLER', 1, 'Test NT-01', 140.0000000000, 1, 2474, '2021-11-24 13:58:35', 2555, '2022-01-11 09:49:10',
        '{"config": [], "methods": {"deposit": {"type": "deposit", "equation": "+(a-b)", "variables": [{"var": "a", "value": "total_amount"}, {"var": "b", "value": "company_fee"}]}, "withdraw": {"type": "withdraw", "equation": "-(a+b)", "variables": [{"var": "a", "value": "customer_amount"}, {"var": "b", "value": "company_fee"}]}}}',
        '[]');


insert into customer_group_gateway (cgg_gateway_id, cgg_customer_group_id, cgg_conditions, cgg_created_by,
                                    cgg_created_at, cgg_updated_by, cgg_updated_at)
values (1, 1, 'customer.currency == "EUR" and transaction.payment_option == "BITCOIN"', 1, '2019-05-10 09:19:15', null,
        null),
       (2, 1, 'customer.currency == "EUR" and transaction.payment_option == "NETELLER"', 1, '2019-05-16 08:34:03', 10,
        '2019-06-14 09:06:23'),
       (3, 1, 'customer.currency == "EUR" and transaction.payment_option == "SKRILL"', 10, '2019-06-14 08:57:12', null,
        null),
       (4, 1, 'transaction.type == "bonus"', 10, '2019-06-14 09:07:36', 10, '2019-08-07 11:59:54'),
       (5, 2, 'true', 1, '2019-07-04 03:55:21', null, null),
       (6, 1, 'customer.currency == "EUR" and transaction.payment_option == "SKRILL"', 2267, '2021-06-25 11:59:45',
        null, null),
       (7, 2, 'customer.currency == "EUR" and transaction.payment_option == "SKRILL"', 2474, '2021-06-28 11:26:32',
        null, null),
       (8, 2, 'customer.currency == "EUR" and transaction.payment_option == "NETELLER"', 2555, '2021-06-29 07:50:37',
        null, null),
       (9, 2, 'customer.currency == "EUR" and transaction.payment_option == "TEST_REG"', 2474, '2021-06-29 08:31:06',
        null, null),
       (10, 1, 'customer.currency == "EUR" and transaction.payment_option == "SKRILL"', 2474, '2021-11-24 13:44:29',
        null, null),
       (10, 2, 'customer.currency == "EUR" and transaction.payment_option == "SKRILL"', 2474, '2021-11-24 13:59:44',
        null, null),
       (11, 1, 'customer.currency == "EUR" and transaction.payment_option == "NETELLER"', 2474, '2021-11-24 13:59:02',
        null, null),
       (11, 2, 'customer.currency == "EUR" and transaction.payment_option == "NETELLER"', 2474, '2021-11-24 13:59:29',
        null, null);


INSERT INTO migrations (version)
VALUES ('20190320110108');
INSERT INTO migrations (version)
VALUES ('20190325100148');
INSERT INTO migrations (version)
VALUES ('20190328204900');
INSERT INTO migrations (version)
VALUES ('20190329101303');
INSERT INTO migrations (version)
VALUES ('20190401061241');
INSERT INTO migrations (version)
VALUES ('20190403132914');
INSERT INTO migrations (version)
VALUES ('20190405112458');
INSERT INTO migrations (version)
VALUES ('20190416114138');
INSERT INTO migrations (version)
VALUES ('20190426054101');
INSERT INTO migrations (version)
VALUES ('20190429131115');
INSERT INTO migrations (version)
VALUES ('20190502130404');
INSERT INTO migrations (version)
VALUES ('20190515091615');
INSERT INTO migrations (version)
VALUES ('20190521072449');
INSERT INTO migrations (version)
VALUES ('20190531062918');
INSERT INTO migrations (version)
VALUES ('20190604050751');
INSERT INTO migrations (version)
VALUES ('20190610095147');
INSERT INTO migrations (version)
VALUES ('20190626102032');
INSERT INTO migrations (version)
VALUES ('20190731041252');
INSERT INTO migrations (version)
VALUES ('20191008083925');
INSERT INTO migrations (version)
VALUES ('20200109114419');
INSERT INTO migrations (version)
VALUES ('20200122042621');
INSERT INTO migrations (version)
VALUES ('20200204083254');
INSERT INTO migrations (version)
VALUES ('20200316053525');
INSERT INTO migrations (version)
VALUES ('20200326052808');
INSERT INTO migrations (version)
VALUES ('20200415041006');
INSERT INTO migrations (version)
VALUES ('20201112074811');
INSERT INTO migrations (version)
VALUES ('20210527114741');
INSERT INTO migrations (version)
VALUES ('20210719063234');
INSERT INTO migrations (version)
VALUES ('20211203154111');

INSERT INTO migrations_payment_options (id, migration, batch) VALUES (1, '2020_07_24_031751_create_paymentoptions_table', 1);
INSERT INTO migrations_payment_options (id, migration, batch) VALUES (2, '2020_07_24_031751_create_paymentoptions_table', 2);
INSERT INTO migrations_payment_options (id, migration, batch) VALUES (3, '2020_09_02_092759_create_customer_payment_options', 1);
INSERT INTO migrations_payment_options (id, migration, batch) VALUES (4, '2020_09_02_092759_create_customer_payment_options', 2);
INSERT INTO migrations_payment_options (id, migration, batch) VALUES (5, '2020_09_22_084850_rename_active_column_to_enable', 1);
INSERT INTO migrations_payment_options (id, migration, batch) VALUES (6, '2021_03_09_044407_create_ether_transactions_table', 1);
INSERT INTO migrations_payment_options (id, migration, batch) VALUES (7, '2021_03_17_120056_create_jobs_table', 1);
INSERT INTO migrations_payment_options (id, migration, batch) VALUES (8, '2021_03_17_120103_create_failed_jobs_table', 1);
INSERT INTO migrations_payment_options (id, migration, batch) VALUES (9, '2021_05_07_043013_add_ether_transaction_status', 1);
INSERT INTO migrations_payment_options (id, migration, batch) VALUES (10, '2021_08_27_042427_add_precision_to_ether_transactions', 1);
INSERT INTO migrations_payment_options (id, migration, batch) VALUES (11, '2021_09_15_064648_add_contract_address_to_payment_options', 1);
INSERT INTO migrations_payment_options (id, migration, batch) VALUES (12, '2021_09_16_083821_create_job_batches_table', 1);
INSERT INTO migrations_payment_options (id, migration, batch) VALUES (13, '2021_09_26_104922_add_unique_hash_constraint_to_ether_transactions', 1);

INSERT INTO migrations_transactions (id, migration, batch) VALUES (1, '2021_09_06_044855_create_transaction_rule_sets_table', 1);
INSERT INTO currency (currency_id, currency_updated_by, currency_code, currency_name, currency_rate, currency_created_by, currency_created_at, currency_updated_at) VALUES (1, null, 'EUR', 'Euro', 1.0000000000, 1, '2022-01-10 09:11:04', null);
