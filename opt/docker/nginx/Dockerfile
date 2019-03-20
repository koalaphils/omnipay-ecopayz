FROM nginx:alpine

COPY /opt/docker/nginx/site.conf /etc/nginx/conf.d/default.conf
COPY /opt/docker/nginx/upstream.conf /etc/nginx/conf.d/hosts.tmp

RUN mkdir -p /var/www/html/web && chmod 777 /var/www/html/web

CMD /bin/sh -c "envsubst < /etc/nginx/conf.d/hosts.tmp > /etc/nginx/conf.d/hosts.conf && exec nginx -g 'daemon off;'"