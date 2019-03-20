cd /var/www/html

if [ ! -d vendor ]; then
    if [[ $SYMFONY_ENV = "dev" ]]; then
        ln -s vendor_dev vendor
    else
        ln -s vendor_non_dev vendor
    fi
fi

if [ -f /run/secrets/env ]; then
    source /run/secrets/env
fi

if [[ ! -z "$DATABASE_PASSWORD_FILE" ]]; then
    export DATABASE_PASSWORD=$(cat "$DATABASE_PASSWORD_FILE")
fi

if [[ ! -z "$BA_TOKEN_FILE" ]]; then
    export BA_TOKEN=$(cat "$BA_TOKEN_FILE")
fi

envsubst < app/config/parameters.yml.dist > app/config/parameters.yml

php app/console cache:clear
php app/console theme:apply euro
php app/console assets:install --symlink --relative
php app/console assetic:dump
php app/console app:email-setup
php app/console app:referral-tools-setup

exec "$@"