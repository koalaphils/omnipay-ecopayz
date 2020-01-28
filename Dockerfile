FROM php:7.3-fpm-buster  as base

RUN  set -eux; \
  apt-get update; \
  #Install runtime dependencies
  apt-get install -y --no-install-recommends \
    cron \
    imagemagick \
    netcat \
    openssh-server \
    rsync \
    unzip \
    libevent-2.1 \
    libevent-openssl-2.1 \
    libevent-extra-2.1 \
    libfreetype6 \
    libjpeg62-turbo \
    libpng16-16 \
    libxpm4 \
    libzip4 \
    ; \
    cd /tmp; \
    #Install extra libraries needed
    curl -sSL https://github.com/redis/hiredis/archive/v0.13.3.tar.gz -o hiredis.tar.gz; \
    tar -xvzf hiredis.tar.gz; \
    cd hiredis-0.13.3; \
    make -j "$(nproc)" && make install; \
  apt-mark manual '.*' > /dev/null

#Install dev dependencies and extensions
RUN set -eux; \
  apt-get update; \
  savedAptMark="$(apt-mark showmanual)"; \
  apt-get install -y --no-install-recommends \
    $PHPIZE_DEPS \
    gettext \
    git \
    libmagickwand-dev \
    libevent-dev \
    libfreetype6-dev \
    libicu-dev \
    libjpeg-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libssl-dev \
    libwebp-dev \
    libxpm-dev \
    libzip-dev \
    libbz2-dev \
    ${PHP_EXTRA_BUILD_DEPS:-} \
    ; \
  export CFLAGS="$PHP_CFLAGS" CPPFLAGS="$PHP_CPPFLAGS" LDFLAGS="$PHP_LDFLAGS" \
  ; \
  docker-php-ext-configure zip \
    --with-libzip=/usr/include \
  ; \
  docker-php-ext-configure gd \
    --with-gd \
    --with-freetype-dir=/usr/include/ \
    --with-jpeg-dir=/usr/include/ \
    --with-webp-dir=/usr/include/ \
    --with-xpm-dir=/usr/include/ \
    --with-png-dir=/usr/include/ \
  ; \
  docker-php-ext-install -j$(nproc) \
    bz2 \
    gd \
    gettext \
    intl \
    opcache \
    pcntl \
    pdo_mysql \
    sockets \
    zip \
  ; \
  pecl install \
    apcu \
    event \
    igbinary \
    imagick \
    redis \
    xdebug \
  ; \
  docker-php-ext-enable \
    apcu \
    event \
    igbinary \
    imagick \
    intl \
    opcache \
    redis \
    sockets \
  ; \
  cp /usr/bin/envsubst /usr/local/bin/envsubst; \
  cd /tmp; \
  git clone https://github.com/nrk/phpiredis.git; \
  cd phpiredis; \
  phpize; \
  ./configure --enable-phpiredis --with-hiredis-dir=/usr/local; \
  make -j "$(nproc)" && make install; \
  echo "extension=phpiredis.so" > /usr/local/etc/php/conf.d/phpiredis.ini

#Reset and do cleanup
RUN set -eux; \
  savedAptMark="$(apt-mark showmanual)"; \
  apt-mark auto '.*' > /dev/null; \
  [ -z "$savedAptMark" ] || apt-mark manual $savedAptMark; \
  apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImport=false; \
  rm -rf /tmp/* ~/.pearrc /var/lib/apt/lists/*; \
  php --version

FROM composer:latest as vendor
COPY composer.json /app/composer.json
COPY composer.lock /app/composer.lock
RUN cd /app && composer install --ignore-platform-reqs --apcu-autoloader -aq --no-scripts --no-interaction --no-suggest --no-dev --prefer-dist --no-autoloader

FROM base as prod
ENV TIMEZONE=Etc/GMT+4 \
    UPLOAD_FOLDER=/uploads \
    SYMFONY_ENV=prod \
    APP_SECRET=ThisTokenIsNotSoSecretChangeIt \
    DATABASE_DRIVER=pdo_sqlite \
    DATABASE_HOST=127.0.0.1 \
    DATABASE_NAME= \
    DATABASE_USER= \
    DATABASE_PORT=3306 \
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
    WS_REALM=realm \
    WS_URL=ws:// \
    WS_WAMP_URL=http:// \
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
    BC_CALLBACK_HOST=http://localhost \
    TRUSTED_PROXIES=127.0.0.1 \
    PIN_API_URL=http://temp \
    PIN_API_AGENT_KEY=temp \
    PIN_API_SECRET_KEY=temp \
    PIN_AGENT_CODE=temp \
    TWILIO_SID= \
    TWILIO_TOKEN= \
    TWILIO_FROM= \
    REDIS_HOST=localhost \
    REDIS_PORT=6379 \
    REDIS_DATABASE=0 \
    MSERVICE_USERNAME= \
    MSERVICE_PASSWORD= \
    SSH_USER=piwi \
    SSH_PASS=piwipass \
    CUSTOMER_FOLDER= \
    AWS_SDK_VERSION= \
    AWS_REGION= \
    AWS_KEY= \
    AWS_SECRET= \
    AWS_S3_VERSION= \
    AWS_BUCKET= 

WORKDIR /var/www/html
RUN sed -i "s/;emergency_restart_threshold\s*=\s*.*/emergency_restart_threshold = 10/g" /usr/local/etc/php-fpm.conf \
  && sed -i "s/;emergency_restart_interval\s*=\s*.*/emergency_restart_interval = 1m/g" /usr/local/etc/php-fpm.conf \
  && sed -i "s/;process_control_timeout\s*=\s*.*/process_control_timeout = 10s/g" /usr/local/etc/php-fpm.conf
COPY /opt/docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY /opt/docker/php/php.ini  /usr/local/etc/php/php.ini
COPY /opt/docker/php/*.ini  /usr/local/etc/php/conf.d/
COPY /opt/docker/php/ssh_config /etc/ssh/ssh_config
RUN mkdir -p /var/log/php7 && chmod -Rf 777 /var/log/php7
COPY --from=vendor /usr/bin/composer /usr/bin/composer

COPY app /var/www/html/app
COPY src /var/www/html/src
COPY themes /var/www/html/themes
COPY var /var/www/html/var
COPY web /var/www/html/web
#COPY /opt/docker/php/cronjobs /etc/cron.d/crontab
#RUN chmod 0644 /etc/cron.d/crontab && touch /var/log/cron.log && /usr/bin/crontab /etc/cron.d/crontab
COPY /opt/docker/php/entrypoint*.sh /
RUN chmod +x /entrypoint*.sh
COPY --from=vendor /app/vendor /var/www/html/vendor
COPY --from=vendor /app/composer.* /var/www/html/
RUN chmod -Rf 777 var/
RUN composer dumpautoload -oa --apcu --no-dev --no-interaction

ENTRYPOINT ["/bin/sh", "/entrypoint.sh"]
EXPOSE 9000
CMD ["php-fpm", "--nodaemonize"]

FROM nginx:latest as webservice
ENV PHP_SSH_USER=piwi \
    PHP_SSH_PASS=piwipass \
    TIMEZONE=Etc/GMT+4
RUN apt-get update -y \
    && apt-get install --no-install-recommends -y \
    openssh-client \
    netcat \
    rsync \
    sshpass \
    tzdata
COPY ./opt/docker/nginx/site.conf /etc/nginx/conf.d/default.tmp
COPY ./opt/docker/nginx/gzip.conf /etc/nginx/conf.d/gzip.conf
COPY ./opt/docker/nginx/open_file_cache.conf /etc/nginx/conf.d/open_file_cache.conf
COPY ./opt/docker/nginx/proxy_headers.conf /etc/nginx/conf.d/proxy_headers.conf
COPY ./opt/docker/nginx/nginx.conf /etc/nginx/nginx.conf

RUN mkdir -p /var/www/html/web && chmod 777 /var/www/html/web
COPY --from=prod /var/www/html/web /var/www/html/web
COPY --from=prod /var/www/html/themes /var/www/html/themes
COPY --from=prod /var/www/html/src /var/www/html/src
COPY /opt/docker/nginx/entrypoint* /
#COPY /opt/docker/nginx/cronjobs /
RUN chmod +x /entrypoint*
ENTRYPOINT ["/bin/sh", "/entrypoint.sh"]
CMD /bin/sh -c "exec nginx -g 'daemon off;'"
