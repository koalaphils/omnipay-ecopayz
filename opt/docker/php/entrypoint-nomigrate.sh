#!/usr/bin/env bash 
set -eux

nc -z ${DATABASE_HOST} ${DATABASE_PORT}

cd /var/www/html

#set +u
#if [ -z "${REDIS_REPLICA_HOST}" ]; then
#  export REDIS_REPLICA_HOST="${REDIS_HOST}"
#fi
#if [ -z "${REDIS_REPLICA_PORT}" ]; then
#  export REDIS_REPLICA_PORT="${REDIS_PORT}"
#fi
#set -u

cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo "${TIMEZONE}" > /etc/timezone
sed -i "s|;date.timezone\s*=.*|date.timezone = ${TIMEZONE}|g" /usr/local/etc/php/conf.d/php.ini
mkdir -p ${UPLOAD_FOLDER}

rm -f app/config/parameters.yml
envsubst < app/config/parameters.yml.dist > app/config/parameters.yml

if [ "$SYMFONY_ENV" = "dev" ]; then
    docker-php-ext-enable xdebug
    sed -i "s/apc.enabled\s*=\s*.*/apc.enabled = 0/g" /usr/local/etc/php/conf.d/apcu.ini
    sed -i "s/\(xdebug\..*\)=\s*.*/\1=1/g" /usr/local/etc/php/conf.d/xdebug.ini
    sed -i "s/apc.enable_cli\s*=\s*.*/apc.enable_cli = 0/g" /usr/local/etc/php/conf.d/apcu.ini
    sed -i "s/opcache.validate_timestamps\s*=\s*.*/opcache.validate_timestamps = 1/g" /usr/local/etc/php/conf.d/opcache.ini
    composer install --dev --apcu-autoloader -aq --ignore-platform-reqs --no-interaction --prefer-dist
    composer dumpautoload -oa --apcu --no-interaction
    php app/console theme:apply euro --remove
    composer symfony-scripts --no-interaction
else
    composer symfony-scripts --no-dev --no-interaction
fi

php app/console theme:apply euro --no-interaction
php app/console cache:clear --no-warmup --no-optional-warmers --no-interaction
php app/console app:email-setup
php app/console app:referral-tools-setup

mkdir -p var/logs
mkdir -p var/logs/blockchain
mkdir -p var/cache/${SYMFONY_ENV}/jms_serializer
mkdir -p var/cache/${SYMFONY_ENV}/profiler
mkdir -p var/cache/${SYMFONY_ENV}/jms_diextra/doctrine
mkdir -p var/cache/${SYMFONY_ENV}/jms_diextra/metadata
mkdir -p var/cache/${SYMFONY_ENV}/jms_aop
mkdir -p var/spool/default
touch var/logs/${SYMFONY_ENV}.log

chown -Rf www-data var
chmod -R 777 ./var/

set +e
id -u ${SSH_USER}
if [ $? -ne 0 ]; then
  adduser ${SSH_USER}
fi

echo "${SSH_USER}:${SSH_PASS}" | chpasswd
ssh-keygen -A

if [ $(grep -c Alpine /etc/issue) -ne 0 ]; then
`which sshd`
elif [ $(grep -c Debian /etc/issue) -ne 0 ]; then
/etc/init.d/ssh start
fi

printenv | sed 's/^\([^=.]*\)=\(.*\)$/export \1="\2"/g' > /env.sh

exec "$@"