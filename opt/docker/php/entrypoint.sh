#!/usr/bin/env bash
set -eux

nc -z ${DATABASE_HOST} ${DATABASE_PORT}

set +u
if [ -z "${REDIS_REPLICA_HOST}" ]; then
  export REDIS_REPLICA_HOST="${REDIS_HOST}"
fi
if [ -z "${REDIS_REPLICA_PORT}" ]; then
  export REDIS_REPLICA_PORT="${REDIS_PORT}"
fi

if [ -z "${DATABASE_REPLICA_HOST}" ]; then
  export DATABASE_REPLICA_HOST="${DATABASE_HOST}"
fi


cd /var/www/html
cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo "${TIMEZONE}" > /etc/timezone
sed -i "s|;date.timezone\s*=.*|date.timezone = ${TIMEZONE}|g" /usr/local/etc/php/conf.d/timezone.ini
mkdir -p ${UPLOAD_FOLDER}
mkdir -p var/logs
mkdir -p var/logs/blockchain
mkdir -p var/cache/${SYMFONY_ENV}/jms_serializer
mkdir -p var/cache/${SYMFONY_ENV}/profiler
mkdir -p var/cache/${SYMFONY_ENV}/jms_diextra/doctrine
mkdir -p var/cache/${SYMFONY_ENV}/jms_diextra/metadata
mkdir -p var/cache/${SYMFONY_ENV}/jms_aop
mkdir -p var/cache/${SYMFONY_ENV}/twig
mkdir -p var/spool/default
touch var/logs/${SYMFONY_ENV}.log

rm -f app/config/parameters.yml
envsubst < app/config/parameters.yml.dist > app/config/parameters.yml

if [ "$SYMFONY_ENV" = "dev" ]; then
  sed -i "s/opcache.revalidate_freq\s*=\s*.*/opcache.revalidate_freq = 0/g" /usr/local/etc/php/conf.d/opcache.ini
elif [ "$SYMFONY_ENV" = "prod" ]; then
  export COMPOSER_PARAMS="--no-dev"
fi

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
chmod 777 -R var


exec "$@"
