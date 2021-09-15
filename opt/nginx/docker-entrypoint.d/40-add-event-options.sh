#!/bin/sh
if [ ! -f /etc/nginx/nginx.conf.orig ]; then
    cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.orig;
fi
cp /etc/nginx/nginx.conf.orig /etc/nginx/nginx.conf
sed -i 's/\(worker_connections\)\(.*\)$/\1\2\n    use epoll;\n    multi_accept on;/' /etc/nginx/nginx.conf
