#!/bin/sh
# vim:sw=2:ts=2:sts=2:et

set -eu
APP_ENV=${ENVIRONMENT:-prod}
WITH_REPORTS=${WITH_REPORTS:-false}
WITH_PAYMENT_OPTIONS=${WITH_PAYMENT_OPTIONS:-false}
WITH_RISK_SETTING=${WITH_RISK_SETTING:-false}
WITH_CORRECT_SCORE=${WITH_CORRECT_SCORE:-false}

export PHPREGEX='^/app\.php(/|$)'

if [ "${APP_ENV}" != "prod" ]; then
  export PHPREGEX='^/(.*)\.php(/|$)'
fi

rm -f /etc/nginx/conf.d/backoffice.conf.location
rm -f /etc/nginx/conf.d/default.conf
envsubst "\$PHPREGEX \$FORCEHTTPS" < /etc/nginx/templates/backoffice.conf.location > /etc/nginx/conf.d/backoffice.conf.location

rm -f /etc/nginx/templates/default.conf.template && cp /etc/nginx/templates/default.conf.template.src /etc/nginx/templates/default.conf.template

if [ "${APP_ENV}" != "prod" ]; then
  sed -i "s/internal.*//g" /etc/nginx/conf.d/backoffice.conf.location
  sed -i "s/access_log.*//g" /etc/nginx/conf.d/00_log.conf
  sed -i "s/app\.php/app_dev.php/g" /etc/nginx/templates/default.conf.template
  sed -i "s/app\.php/app_dev.php/g" /etc/nginx/conf.d/backoffice.conf.location
fi

if [ "${WITH_REPORTS}" = "false" ]; then
  sed -i "s/\S*include\S*.*reports\.conf\.location.*$//g" /etc/nginx/templates/default.conf.template
fi

if [ "${WITH_PAYMENT_OPTIONS}" = "false" ]; then
  sed -i "s/\S*include\S*.*payment-options\.conf\.location.*$//g" /etc/nginx/templates/default.conf.template
fi

if [ "${WITH_RISK_SETTING}" = "false" ]; then
  sed -i "s/\S*include\S*.*risk-setting\.conf\.location.*$//g" /etc/nginx/templates/default.conf.template
fi

if [ "${WITH_CORRECT_SCORE}" = "false" ]; then
  sed -i "s/\S*include\S*.*correct-score\.conf\.location.*$//g" /etc/nginx/templates/default.conf.template
fi