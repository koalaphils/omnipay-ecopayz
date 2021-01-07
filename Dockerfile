FROM koalaphils/php:7.3-fpm  as base

RUN  set -eux; \
  apt-get update; \
  #Install runtime dependencies
  apt-get install -y --no-install-recommends \
    cron \
    netcat \
    openssh-server \
    rsync \
    ; \
  rm -rf /tmp/* ~/.pearrc /var/lib/apt/lists/* /var/cache/*; \
  php --version

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
    AWS_BUCKET= \
    EVOLUTION_SERVICE_URL= \
    PINNACLE_SERVICE_URL= \
    API_GATEWAY_URL= \
    SESSION_EXPIRATION_TIME=

WORKDIR /var/www/html
RUN sed -i "s/;emergency_restart_threshold\s*=\s*.*/emergency_restart_threshold = 10/g" /usr/local/etc/php-fpm.conf \
  && sed -i "s/;emergency_restart_interval\s*=\s*.*/emergency_restart_interval = 1m/g" /usr/local/etc/php-fpm.conf \
  && sed -i "s/;process_control_timeout\s*=\s*.*/process_control_timeout = 10s/g" /usr/local/etc/php-fpm.conf
COPY /opt/docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY /opt/docker/php/*.ini  /usr/local/etc/php/conf.d/
COPY /opt/docker/php/ssh_config /etc/ssh/ssh_config

COPY composer.json /var/www/html/composer.json
COPY composer.lock /var/www/html/composer.lock
RUN mkdir -p /var/log/php7 && chmod -Rf 777 /var/log/php7 \
  ; composer config --global use-github-api false \
  ; rm -rf vendor && mkdir -p vendor && php -d memory_limit=-1 `which composer` install -no --apcu-autoloader --no-scripts --no-progress --no-autoloader --no-cache \
  ;
VOLUME /var/www/html/vendor

COPY app /var/www/html/app
COPY src /var/www/html/src
COPY themes /var/www/html/themes
COPY var /var/www/html/var
COPY web /var/www/html/web
COPY /opt/docker/php/cronjobs /etc/cron.d/crontab
RUN chmod 0644 /etc/cron.d/crontab \
 ; touch /var/log/cron.log \
 ; /usr/bin/crontab /etc/cron.d/crontab \
 ; chmod -Rf 777 var/ \
 ;

COPY /opt/docker/php/entrypoint.sh /entrypoint.sh
COPY /opt/docker/php/entrypoint.sh /entrypoint-nomigrate.sh
RUN sed -i "s|exec \"\$@\"||g" /entrypoint.sh \
  ; sed -i "s|exec \"\$@\"||g" /entrypoint-nomigrate.sh \
  ; echo "composer dumpautoload --apcu -no --no-scripts \$COMPOSER_PARAMS;\ncomposer install -no --apcu-autoloader --no-progress;\ncomposer run db-migrate;\nchown -Rf www-data /var/www/html/var\nexec \"\$@\";" >> /entrypoint.sh \
  ; echo "composer dumpautoload --apcu -no --no-scripts \$COMPOSER_PARAMS;\ncomposer install -no --apcu-autoloader --no-progress --no-scripts;\ncomposer run symfony-scripts;\ncomposer run cleancache;\nexec \"\$@\";" >> /entrypoint-nomigrate.sh \
  ;
RUN chmod +x /entrypoint*.sh
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
