#!/bin/sh
set -e

echo "==> Generating JWT keys if missing..."
if [ ! -f /var/www/html/config/jwt/private.pem ]; then
    php /var/www/html/bin/console lexik:jwt:generate-keypair --overwrite --no-interaction
fi

echo "==> Warming up cache..."
php /var/www/html/bin/console cache:warmup --env=prod --no-debug

echo "==> Running database migrations..."
php /var/www/html/bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "==> Starting PHP-FPM..."
php-fpm -D

echo "==> Starting Nginx..."
nginx -g "daemon off;"
