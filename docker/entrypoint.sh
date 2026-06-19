#!/bin/sh
set -e

if [ -f /var/www/html/.env ]; then
    export $(grep -v '^#' /var/www/html/.env | xargs)
fi

if [ ! -d /var/www/html/vendor ]; then
    composer install --no-interaction --prefer-dist
fi

exec apache2-foreground
