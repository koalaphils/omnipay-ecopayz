FROM zimi/php:7.4-fpm  as base

RUN  set -eux; \
  apt-get update; \
  #Install runtime dependencies
  apt-get install -y --no-install-recommends \
    cron \
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
    DATABASE_REPLICA_HOST= \
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
    REDIS_REPLICA_HOST=localhost \
    REDIS_REPLICA_PORT=6379 \
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
    SESSION_EXPIRATION_TIME= \
    ACCESS_TOKEN_EXPIRES_IN=

WORKDIR /var/www/html
COPY opt/php/*.ini $PHP_INI_DIR/conf.d/
COPY opt/php/www.conf $PHP_INI_DIR/../php-fpm.d/

COPY /opt/php/cronjobs /etc/cron.d/crontab
RUN chmod 0644 /etc/cron.d/crontab \
 ; touch /var/log/cron.log \
 ; /usr/bin/crontab /etc/cron.d/crontab \
 ;

COPY opt/nginx /nginx
COPY --from=nginx /docker-entrypoint.d/* /nginx/docker-entrypoint.d/
RUN chmod a+x /nginx/docker-entrypoint.d/*
VOLUME /nginx/conf
VOLUME /nginx/template
VOLUME /nginx/docker-entrypoint.d

COPY . /var/www/html
RUN mkdir -p /var/log/php7 && chmod -Rf 777 /var/log/php7 \
RUN composer config --global use-github-api false \
  ; rm -rf vendor && mkdir -p vendor \
  ; php -d memory_limit=-1 `which composer` install --no-scripts --no-autoloader || exit 1 \
  ; composer dumpautoload -no --apcu --no-scripts \
  ;
VOLUME /var/www/html/vendor

COPY /opt/php/entrypoint.sh /entrypoint.sh
COPY /opt/php/entrypoint.sh /entrypoint-nomigrate.sh
RUN sed -i "s|exec \"\$@\"||g" /entrypoint.sh \
  ; sed -i "s|exec \"\$@\"||g" /entrypoint-nomigrate.sh \
  ; echo "composer run post-install-cmd;\ncomposer run db-migrate;\nchmod ugo+w -R var\nexec \"\$@\";" >> /entrypoint.sh \
  ; echo "composer run post-install-cmd;\nchmod ugo+w -R var\nexec \"\$@\";" >> /entrypoint-nomigrate.sh \
  ;
RUN chmod +x /entrypoint*.sh
ENTRYPOINT ["/bin/sh", "/entrypoint.sh"]
CMD ["php-fpm"]

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
COPY --from=prod /nginx/conf /etc/nginx/conf.d
COPY --from=prod /nginx/template /etc/nginx/templates
COPY --from=prod /nginx/docker-entrypoint.d /docker-entrypoint.d
RUN mkdir -p /var/www/html && chmod 777 /var/www/html
COPY --from=prod /var/www/html /var/www/html
