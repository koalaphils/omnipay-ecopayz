cd /backoffice
envsubst '\$SYMFONY_ENV \$DATABASE_HOST \$DATABASE_NAME \$DATABASE_USER \$DATABASE_PASSWORD' < /backoffice/app/config/parameters.yml.dist > /backoffice/app/config/parameters.yml
sed "/^\s*header.*/g" -i ./web/app_dev.php
sed "/^\s*exit.*/g" -i ./web/app_dev.php
composer install
php app/console doctrine:migrations:migrate --env="${SYMFONY_ENV}"
php app/console theme:apply euro --remove --env="${SYMFONY_ENV}"
php app/console theme:apply euro --env="${SYMFONY_ENV}"
php app/console assets:install --env="${SYMFONY_ENV}"
php app/console assetic:dump
php app/console cache:warmup