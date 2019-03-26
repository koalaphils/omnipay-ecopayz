FROM alpine:3.9 as base

ENV TIMEZONE=Asia/Manila

RUN apk add --update imagemagick \
    git \
    php7 \
    php7-cli \
    php7-curl \
    php7-openssl \
    php7-json \
    php7-fpm \
    php7-pdo \
    php7-mysqli \
    php7-mbstring \
    php7-gd \
    php7-dom \
    php7-xml \
    php7-posix \
    php7-intl \
    php7-apcu \
    php7-phar \
    php7-zlib \
    php7-fileinfo \
    php7-simplexml \
    php7-tokenizer \
    php7-xmlwriter \
    php7-bz2 \
    php7-ctype \
    php7-session \
    php7-pdo_mysql \
    php7-zip \
    php7-iconv \
    php7-imagick \
    gettext \
    grep

FROM composer:1.8 as vendor

COPY composer.json /app/composer.json
COPY composer.lock /app/composer.lock
COPY app/AppKernel.php /app/app/AppKernel.php
COPY app/AppCache.php /app/app/AppCache.php

RUN cd /app && composer install --ignore-platform-reqs --no-scripts --no-interaction --no-suggest --no-dev
RUN cp -R /app/vendor /app/vendor_dev
RUN cd /app && COMPOSER_VENDOR_DIR=vendor_dev composer install --ignore-platform-reqs --no-scripts --no-interaction --no-suggest

FROM base

ENV UPLOAD_FOLDER=/uploads \
    SYMFONY_ENV=prod \
    APP_SECRET=ThisTokenIsNotSoSecretChangeIt \
    DATABASE_HOST= \
    DATABASE_NAME= \
    DATABASE_USER= \
    DATABASE_PASSWORD= \
    MAIL_TRANSPORT=smtp \
    MAIL_HOST= \
    MAIL_USER= \
    MAIL_PASSWORD= \
    MAIL_PORT= \
    MAIL_FROM= \
    MAIL_CC= \
    MAIL_AFFILIATE_FROM= \
    MAIL_ENCRYPTION=tls \
    JWT_KEY=YourSampleKeyHere \
    WS_SCHEME=ws \
    WS_HOST= \
    WS_RELAM= \
    WS_PORT=8080 \
    WS_WAMP_PORT=9092 \
    BA_URL= \
    BA_TOKEN= \
    BA_TOKEN_TYPE=Bearer \
    AC_URL= \
    AC_DOMAIN= \
    AO_DOMAIN= \
    CUSTOMER_TEMP_PASSWORD=super@c66p@$$w0rd \
    ECOPAYZ_TEST_MODE=false \
    SLACK_TOKEN= \
    SLACK_CHANNEL= \
    BC_KEY= \
    BC_WALLET_URL= \
    BC_XPUB_HOST= \
    BC_XPUB_PORT=22 \
    BC_XPUB_USER= \
    BC_XPUB_PASSWORD= \
    BC_XPUB_PK= \
    TRUSTED_PROXIES=[127.0.0.1] \
    PIN_API_URL= \
    PIN_API_AGENT_KEY= \
    PIN_API_SECRET_KEY= \
    PIN_AGENT_CODE= \
    TWILIO_SID= \
    TWILIO_TOKEN= \
    TWILIO_FROM=

RUN mkdir $UPLOAD_FOLDER

COPY /opt/docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

COPY /opt/docker/php/www.conf /etc/php7/php-fpm.d/www.conf
COPY /opt/docker/php/php.ini /etc/php7/php.ini

WORKDIR /var/www/html

COPY --from=vendor /usr/bin/composer /usr/bin/composer
COPY --from=vendor /app/vendor /var/www/html/vendor_non_dev
COPY --from=vendor /app/vendor_dev /var/www/html/vendor_dev
COPY . /var/www/html/

RUN mkdir -p /var/log/php7 && chmod -Rf 777 /var/log/php7

ENTRYPOINT ["/bin/sh", "/entrypoint.sh"]

EXPOSE 9000

CMD ["/usr/sbin/php-fpm7", "--nodaemonize"]