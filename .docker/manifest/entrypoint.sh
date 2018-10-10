maxretries=100
retries=0

while true; do
    sleep 10;
    database=`/usr/bin/mysql -hdatabase -u${DATABASE_USER} -p${DATABASE_PASSWORD} --skip-column-names -e "show databases like '${DATABASE_NAME}'"`
    if [ "$database" == "$DATABASE_NAME" ]; then
        echo "Database is already up"
        break
    fi

    retries=`expr $retries + 1`

    if [ "$retries" -ge "$maxretries" ]; then
        echo "We have waiting for MySQL too long already; failing; Pls. restart this container."
        exit 1
    fi
done

rm -Rf /etc/nginx/sites-enabled/*
if [[ $SYMFONY_ENV = "dev" ]]; then
    ln -s /etc/nginx/sites-available/dev.conf /etc/nginx/sites-enabled/dev.conf
    # envsubst '\$SYMFONY_ENV \$DATABASE_HOST \$DATABASE_NAME \$DATABASE_USER \$DATABASE_PASSWORD' < /etc/nginx/sites-available/dev.conf > /etc/nginx/sites-enabled/dev.conf && nginx -g 'daemon off;'
    ln -s /etc/nginx/sites-available/docs.conf /etc/nginx/sites-enabled/docs.conf
    # apk add --no-cache --repository http://dl-3.alpinelinux.org/alpine/edge/testing vips-tools vips-dev fftw-dev glib-dev php7-dev php7-pear build-base && pecl install xdebug && echo 'zend_extension=/usr/lib/php7/modules/xdebug.so' >> /etc/php7/php.ini && php -m | grep xdebug
else
    ln -s /etc/nginx/sites-available/prod.conf /etc/nginx/sites-enabled/prod.conf
    # envsubst '\$SYMFONY_ENV \$DATABASE_HOST \$DATABASE_NAME \$DATABASE_USER \$DATABASE_PASSWORD' < /etc/nginx/sites-available/prod.conf > /etc/nginx/sites-enabled/prod.conf && nginx -g 'daemon off;'
fi

chown www:www /backoffice/var -Rf
chown www:www /backoffice/src -Rf
chmod 777 /backoffice/var -Rf
chmod  777 /backoffice/var -Rf
chmod 777 /uploads/
setfacl -R -d -m o::rwx /backoffice/var
setfacl -R -d -m g::rwx /backoffice/var

/bin/sh /opt/setupbo.sh

su www -c '/bin/sh /www-cmd.sh'

/usr/bin/supervisord -n -c /etc/supervisord.conf