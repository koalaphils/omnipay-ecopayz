FROM alpine:3.9 as base
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
    php7-pdo_sqlite \
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

FROM base as prod
ENV UPLOAD_FOLDER=/uploads \
    SYMFONY_ENV=prod \
    APP_SECRET=ThisTokenIsNotSoSecretChangeIt \
    DATABASE_DRIVER=pdo_sqlite \
    DATABASE_HOST=127.0.0.1 \
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
    WS_REALM= \
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
    SLACK_TOKEN=temp \
    SLACK_CHANNEL=temp \
    BC_KEY= \
    BC_WALLET_URL= \
    BC_XPUB_HOST= \
    BC_XPUB_PORT=22 \
    BC_XPUB_USER= \
    BC_XPUB_PASSWORD= \
    BC_XPUB_PK= \
    TRUSTED_PROXIES=[127.0.0.1] \
    PIN_API_URL=http://temp \
    PIN_API_AGENT_KEY=temp \
    PIN_API_SECRET_KEY=temp \
    PIN_AGENT_CODE=temp \
    TWILIO_SID= \
    TWILIO_TOKEN= \
    TWILIO_FROM= \
    APP_TIMEZONE=UTC \
    REDIS_HOST=localhost \
    REDIS_PORT=6379 \
    REDIS_DATABASE=0
    MSERVICE_USERNAME= \
    MSERVICE_PASSWORD= \

RUN mkdir $UPLOAD_FOLDER
COPY /opt/docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
COPY /opt/docker/php/www.conf /etc/php7/php-fpm.d/www.conf
COPY /opt/docker/php/php.ini /etc/php7/php.ini
WORKDIR /var/www/html
COPY --from=vendor /usr/bin/composer /usr/bin/composer
COPY --from=vendor /app/vendor /var/www/html/vendor
COPY . /var/www/html/
RUN envsubst < app/config/parameters.yml.dist > app/config/parameters.yml
RUN composer install --no-dev
RUN php app/console theme:apply euro
RUN php app/console assets:install --symlink --relative
RUN php app/console assetic:dump
RUN rm app/config/parameters.yml
ENV DATABASE_DRIVER=pdo_mysql
RUN mkdir -p /var/log/php7 && chmod -Rf 777 /var/log/php7
RUN chmod -R 777 ./var/
ENTRYPOINT ["/bin/sh", "/entrypoint.sh"]
EXPOSE 9000
CMD ["/usr/sbin/php-fpm7", "--nodaemonize"]

FROM nginx:alpine as webservice
COPY /opt/docker/nginx/site.conf /etc/nginx/conf.d/default.conf
COPY /opt/docker/nginx/upstream.conf /etc/nginx/conf.d/hosts.tmp
RUN mkdir -p /var/www/html/web && chmod 777 /var/www/html/web
COPY --from=prod /var/www/html /var/www/html
RUN rm -Rf /var/www/html/vendor
CMD /bin/sh -c "envsubst < /etc/nginx/conf.d/hosts.tmp > /etc/nginx/conf.d/hosts.conf && exec nginx -g 'daemon off;'"
