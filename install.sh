#!/usr/bin/env bash
# set folder permissions
#
# if you have SELinux
# The directory *app/cache* *app/logs* and *upload_folder* must have a type of  httpd_sys_rw_content_t
# check the directory type
# $ ls -laZ
# if enabled lines below  are not enough you may use chcon for selinux enable systems
# chcon -t httpd_sys_rw_content_t app/cache
# chcon -t httpd_sys_rw_content_t app/logs
#
#
echo "============================================="
echo "                                       ";
echo -e " Installing SUMMIT (BackOffice) for \e[1;32m $SYMFONY_ENV \e[0m environment ";
echo "                                       ";
echo "============================================="
sleep 3;

echo -e '';
echo -e '';
echo -e 'enabling app_dev.php...';
sed "/^\s*header.*/g" -i ./web/app_dev.php;
sed "/^\s*exit.*/g" -i ./web/app_dev.php;

echo -e '';
echo -e '';
echo -e 'installing dependencies...';
composer install;

echo -e '';
echo -e '';
echo -e 'migrating database...';
php ./app/console doctrine:migrations:migrate;

echo -e '';
echo -e '';
echo -e '\e[1;32mmaking app/cache and app/logs writable... \e[0m';

sudo chmod 777 var -R;
sudo setfacl -R -d -m u:nginx:rwx -R -d -m g:apache:rwx var;

echo -e '';
echo -e '';
echo -e '\e[1;32mApplying euro theme...\e[0m';
php ./app/console theme:apply euro;

echo -e '';
echo -e '';
echo -e '\e[1;32mInstalling Assets...\e[0m';
# Install Assets
php ./app/console assets:install;
# Dump Assetics
php ./app/console assetic:dump;

