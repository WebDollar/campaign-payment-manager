#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
    set -- supervisord "$@"
fi

if [ "$1" = 'supervisord' ]; then
    mkdir -p var/cache var/log /etc/supervisor/conf.d /var/log/${APP_NAME} public/media

    if [ "$APP_ENV" != 'prod' ]; then
        composer install --no-dev --prefer-dist --optimize-autoloader --no-progress --no-suggest
        bin/console assets:install --no-interaction --no-debug --env=${APP_ENV}
    fi

    echo "Running Database migrations..."
    if [ "$(ls -A src/Migrations/*.php 2> /dev/null)" ]; then
        bin/console doctrine:migrations:migrate --no-interaction --no-debug --env=${APP_ENV}
    fi

    echo "Creating supervisor config"
    bin/console xterr:supervisor:dump --env=${APP_ENV} --user=${DAEMONS_USER} >> /etc/supervisor/conf.d/supervisord.conf

    echo "Warming cache"
    bin/console cache:warmup --no-debug --env=${APP_ENV}

    chown -R www-data.www-data .
fi

exec "$@"
