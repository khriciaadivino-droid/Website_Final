#!/bin/sh
set -e

export APP_ENV="${APP_ENV:-prod}"
export APP_DEBUG="${APP_DEBUG:-0}"
export PORT="${PORT:-8000}"
export APP_SECRET="${APP_SECRET:-change-me-in-railway-app-secret}"
export JWT_SECRET_KEY="${JWT_SECRET_KEY:-/var/www/html/config/jwt/private.pem}"
export JWT_PUBLIC_KEY="${JWT_PUBLIC_KEY:-/var/www/html/config/jwt/public.pem}"
export JWT_PASSPHRASE="${JWT_PASSPHRASE:-}"
export MIGRATION_LOCK_NAME="${MIGRATION_LOCK_NAME:-website_final_doctrine_migrations}"

acquire_migration_lock() {
    php <<'PHP'
<?php
$databaseUrl = getenv('DATABASE_URL') ?: '';
$lockName = getenv('MIGRATION_LOCK_NAME') ?: 'website_final_doctrine_migrations';

if ($databaseUrl === '') {
    fwrite(STDERR, "DATABASE_URL is required before acquiring the migration lock.\n");
    exit(1);
}

$parts = parse_url($databaseUrl);
if ($parts === false || ($parts['scheme'] ?? '') !== 'mysql') {
    fwrite(STDERR, "Unsupported DATABASE_URL for MySQL advisory lock.\n");
    exit(1);
}

$host = $parts['host'] ?? '127.0.0.1';
$port = (int) ($parts['port'] ?? 3306);
$database = rawurldecode(ltrim($parts['path'] ?? '', '/'));
$username = rawurldecode($parts['user'] ?? '');
$password = rawurldecode($parts['pass'] ?? '');

if ($database === '' || $username === '') {
    fwrite(STDERR, "DATABASE_URL is missing required MySQL connection parts.\n");
    exit(1);
}

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,
    ]);
    $statement = $pdo->prepare('SELECT GET_LOCK(:lock_name, 120)');
    $statement->execute(['lock_name' => $lockName]);
    $acquired = (int) $statement->fetchColumn();

    if ($acquired === 1) {
        exit(0);
    }

    fwrite(STDERR, "Timed out waiting for the migration lock.\n");
    exit(2);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
PHP
}

release_migration_lock() {
    php <<'PHP'
<?php
$databaseUrl = getenv('DATABASE_URL') ?: '';
$lockName = getenv('MIGRATION_LOCK_NAME') ?: 'website_final_doctrine_migrations';

$parts = parse_url($databaseUrl);
if ($parts === false || ($parts['scheme'] ?? '') !== 'mysql') {
    exit(0);
}

$host = $parts['host'] ?? '127.0.0.1';
$port = (int) ($parts['port'] ?? 3306);
$database = rawurldecode(ltrim($parts['path'] ?? '', '/'));
$username = rawurldecode($parts['user'] ?? '');
$password = rawurldecode($parts['pass'] ?? '');

if ($database === '' || $username === '') {
    exit(0);
}

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,
    ]);
    $statement = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
    $statement->execute(['lock_name' => $lockName]);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
PHP
}

write_runtime_env_file() {
    php <<'PHP'
<?php
$envPath = '/var/www/html/.env.local';
$values = [
    'APP_ENV' => getenv('APP_ENV') ?: 'prod',
    'APP_DEBUG' => getenv('APP_DEBUG') ?: '0',
    'APP_SECRET' => getenv('APP_SECRET') ?: 'change-me-in-railway-app-secret',
    'DATABASE_URL' => getenv('DATABASE_URL') ?: 'mysql://placeholder:placeholder@127.0.0.1:3306/app',
    'JWT_SECRET_KEY' => getenv('JWT_SECRET_KEY') ?: '/var/www/html/config/jwt/private.pem',
    'JWT_PUBLIC_KEY' => getenv('JWT_PUBLIC_KEY') ?: '/var/www/html/config/jwt/public.pem',
    'JWT_PASSPHRASE' => getenv('JWT_PASSPHRASE') ?: '',
    'MESSENGER_TRANSPORT_DSN' => getenv('MESSENGER_TRANSPORT_DSN') ?: 'doctrine://default?auto_setup=0',
    'MAILER_DSN' => getenv('MAILER_DSN') ?: 'null://null',
];

$lines = [];
foreach ($values as $key => $value) {
    $escapedValue = str_replace(
        ["\\", "\n", "\r", "'"],
        ["\\\\", "\\n", "\\r", "\\'"],
        (string) $value
    );
    $lines[] = sprintf("%s='%s'", $key, $escapedValue);
}

file_put_contents($envPath, implode(PHP_EOL, $lines) . PHP_EOL);
PHP
}

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

echo "==> Writing runtime .env.local from active environment..."
write_runtime_env_file

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

echo "==> Configuring PHP-FPM to preserve runtime environment..."
if grep -Eq '^[;[:space:]]*clear_env[[:space:]]*=' /usr/local/etc/php-fpm.d/www.conf; then
    sed -i 's/^[;[:space:]]*clear_env[[:space:]]*=.*/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf
else
    printf '\nclear_env = no\n' >> /usr/local/etc/php-fpm.d/www.conf
fi

echo "==> Generating JWT keys if missing..."
if [ ! -f /var/www/html/config/jwt/private.pem ]; then
    php /var/www/html/bin/console lexik:jwt:generate-keypair --overwrite --no-interaction
fi

echo "==> Warming up cache..."
php /var/www/html/bin/console cache:warmup --env=prod --no-debug

echo "==> Waiting for migration lock..."
set +e
acquire_migration_lock
lock_status=$?
set -e

if [ "$lock_status" -eq 0 ]; then
    echo "==> Running database migrations..."
    migration_status=0
    php /var/www/html/bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod || migration_status=$?

    echo "==> Releasing migration lock..."
    release_migration_lock || true

    if [ "$migration_status" -ne 0 ]; then
        exit "$migration_status"
    fi
elif [ "$lock_status" -eq 2 ]; then
    echo "==> Another instance is running migrations. Continuing startup without local migration execution."
else
    echo "==> ERROR: Failed to acquire the migration lock."
    exit "$lock_status"
fi

echo "==> Starting PHP-FPM..."
php-fpm -D

echo "==> Starting Nginx..."
nginx -g "daemon off;"
