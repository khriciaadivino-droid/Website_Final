#!/bin/sh
set -e

export APP_ENV="${APP_ENV:-prod}"
export APP_DEBUG="${APP_DEBUG:-0}"
export PORT="${PORT:-80}"
export APP_SECRET="${APP_SECRET:-change-me-in-railway-app-secret}"
export JWT_SECRET_KEY="${JWT_SECRET_KEY:-/var/www/html/config/jwt/private.pem}"
export JWT_PUBLIC_KEY="${JWT_PUBLIC_KEY:-/var/www/html/config/jwt/public.pem}"
export JWT_PASSPHRASE="${JWT_PASSPHRASE:-}"

if [ -z "$DATABASE_URL" ]; then
    if [ -n "$MYSQL_URL" ]; then
        export DATABASE_URL="$MYSQL_URL"
    elif [ -n "$MYSQLHOST" ] && [ -n "$MYSQLPORT" ] && [ -n "$MYSQLUSER" ] && [ -n "$MYSQLPASSWORD" ] && [ -n "$MYSQLDATABASE" ]; then
        export DATABASE_URL="mysql://${MYSQLUSER}:${MYSQLPASSWORD}@${MYSQLHOST}:${MYSQLPORT}/${MYSQLDATABASE}"
    fi
fi

if [ ! -f /var/www/html/.env ]; then
    echo "==> Creating runtime .env file..."
    cat > /var/www/html/.env <<'EOF'
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=change-me-in-railway-app-secret
DATABASE_URL=mysql://placeholder:placeholder@127.0.0.1:3306/app
JWT_SECRET_KEY=/var/www/html/config/jwt/private.pem
JWT_PUBLIC_KEY=/var/www/html/config/jwt/public.pem
JWT_PASSPHRASE=
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
MAILER_DSN=null://null
EOF
fi

echo "==> Runtime environment: APP_ENV=$APP_ENV"
if [ -n "$DATABASE_URL" ]; then
    echo "==> DATABASE_URL resolved for startup"
else
    echo "==> WARNING: DATABASE_URL is not set"
    echo "==> ERROR: Connect the Railway MySQL variables to this app service or set DATABASE_URL manually."
    exit 1
fi

echo "==> Rendering Nginx config for PORT=$PORT..."
sed "s/__PORT__/${PORT}/g" /etc/nginx/sites-available/default.template > /etc/nginx/sites-available/default

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
