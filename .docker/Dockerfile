ARG BASE_IMAGE
ARG BASE_IMAGE_TAG

FROM $BASE_IMAGE:$BASE_IMAGE_TAG

COPY .docker/manifest/ /
COPY ./ /backoffice/

RUN mkdir /backoffice/vendor && mkdir /backoffice/c3tmp \
    && chown -R www:www /home/www/.composer /backoffice/var /backoffice/web /backoffice/vendor /backoffice/app/config /uploads \
    && setfacl -R -d -m u:www:rwx -R -m u:www:rwx -R -d -m g:www:rwx -R -m g:www:rwx /var/tmp/ /tmp/ /backoffice/var /backoffice/web /uploads /backoffice/vendor /backoffice/app/config

RUN su www -c 'cd /backoffice && composer install --no-scripts'

RUN chmod 755 /entrypoint.sh && chmod 755 /opt/setupbo.sh

RUN sed -i 's/\r$//' /entrypoint-windows.sh && sed -i 's/\r$//' /www-cmd.sh && sed -i 's/\r$//' /opt/setupbo.sh

EXPOSE 80 443 22 8080

CMD ["/bin/sh", "/entrypoint.sh"]