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

if [ -z "${WS_INTERNAL_URL}" ]; then
  export WS_INTERNAL_URL="${WS_URL}"
fi

export MAIL_ENCRYPTION="${MAIL_ENCRYPTION:-tls}"

cd /var/www/html
cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo "${TIMEZONE}" > /etc/timezone
sed -i "s|;date.timezone\s*=.*|date.timezone = ${TIMEZONE}|g" $PHP_INI_DIR/conf.d/timezone.ini
mkdir -p ${UPLOAD_FOLDER}

rm -f app/config/parameters.yml
envsubst < app/config/parameters.yml.dist > app/config/parameters.yml

if [ "$SYMFONY_ENV" = "dev" ]; then
  docker-php-ext-enable xdebug;
  sed -i "s/opcache.revalidate_freq\s*=\s*.*/opcache.revalidate_freq = 0/g" $PHP_INI_DIR/conf.d/opcache.ini
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

exec "$@"