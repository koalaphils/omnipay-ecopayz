#!/usr/bin/env sh
set -eux

#check to see if the PHP host is up and exit when not
#nc -z ${PHPHOST} ${PHPPORT}

cp /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo "${TIMEZONE}" > /etc/timezone

#crontab /cronjobs
#crond -S

#rsync --rsh="sshpass -p ${PHP_SSH_PASS} ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -l ${PHP_SSH_USER}" -lrizc --include 'web/' --include 'src/' --include 'themes/' --exclude '/*'  ${PHPHOST}:/var/www/html/ /var/www/html >&1
#export PHPREGEX='^/app\.php(/|$)'

if [ "${ENVIRONMENT:-prod}" = "dev" ]; then
  export PHPREGEX='^/(.*)\.php(/|$)'
fi

envsubst '${FORCEHTTPS} ${APPHOST} ${PHPHOST} ${PHPPORT} ${PHPREGEX}' < /etc/nginx/conf.d/default.tmp > /etc/nginx/conf.d/default.conf

if [ "${ENVIRONMENT:-prod}" = "dev" ]; then
  sed -i "s/internal.*//g" /etc/nginx/conf.d/default.conf
fi

exec "$@"
