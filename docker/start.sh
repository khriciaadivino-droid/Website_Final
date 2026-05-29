#!/bin/bash
set -eo pipefail

export APP_ENV="${APP_ENV:-prod}"
export APP_DEBUG="${APP_DEBUG:-0}"
export PORT="${PORT:-8000}"
export APP_SECRET="${APP_SECRET:-change-me-in-railway-app-secret}"
export JWT_SECRET_KEY="${JWT_SECRET_KEY:-/var/www/html/config/jwt/private.pem}"
export JWT_PUBLIC_KEY="${JWT_PUBLIC_KEY:-/var/www/html/config/jwt/public.pem}"
export JWT_PASSPHRASE="${JWT_PASSPHRASE:-}"
export MIGRATION_LOCK_NAME="${MIGRATION_LOCK_NAME:-website_final_doctrine_migrations}"

resolve_database_url() {
    php <<'PHP'
<?php
$readEnv = static function (string $key): string {
    $value = getenv($key);

    return is_string($value) ? trim($value) : '';
};

$candidates = [
    $readEnv('DATABASE_URL'),
    $readEnv('DATABASE_PRIVATE_URL'),
    $readEnv('MYSQL_URL'),
    $readEnv('MYSQL_PRIVATE_URL'),
    $readEnv('MYSQL_PUBLIC_URL'),
];

foreach ($candidates as $candidate) {
    if ($candidate !== '') {
        echo $candidate;
        exit(0);
    }
}

$host = $readEnv('MYSQLHOST');
$port = $readEnv('MYSQLPORT') ?: '3306';
$user = $readEnv('MYSQLUSER');
$password = $readEnv('MYSQLPASSWORD');
$database = $readEnv('MYSQLDATABASE');

if ($host !== '' && $user !== '' && $database !== '') {
    echo sprintf(
        'mysql://%s:%s@%s:%s/%s',
        rawurlencode($user),
        rawurlencode($password),
        $host,
        $port,
        rawurlencode($database)
    );
    exit(0);
}

exit(1);
PHP
}

wait_for_database() {
    max_attempts="${DB_WAIT_ATTEMPTS:-60}"
    attempt=1

    echo "==> Waiting for database (up to $max_attempts attempts)..."
    while [ "$attempt" -le "$max_attempts" ]; do
        if php <<'PHP'
<?php
$databaseUrl = getenv('DATABASE_URL') ?: '';
if ($databaseUrl === '') {
    exit(1);
}

$parts = parse_url($databaseUrl);
if ($parts === false || !in_array($parts['scheme'] ?? '', ['mysql', 'mysqli'], true)) {
    exit(1);
}

$host = $parts['host'] ?? '127.0.0.1';
$port = (int) ($parts['port'] ?? 3306);
$database = rawurldecode(ltrim($parts['path'] ?? '', '/'));
$username = rawurldecode($parts['user'] ?? '');
$password = rawurldecode($parts['pass'] ?? '');

if ($database === '' || $username === '') {
    exit(1);
}

$dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $pdo->query('SELECT 1');
    exit(0);
} catch (Throwable) {
    exit(1);
}
PHP
        then
            echo "==> Database is ready."
            return 0
        fi

        echo "==> Database not ready yet (attempt $attempt/$max_attempts)..."
        attempt=$((attempt + 1))
        sleep 2
    done

    echo "==> ERROR: Database did not become ready in time."
    return 1
}

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
if ($parts === false || !in_array($parts['scheme'] ?? '', ['mysql', 'mysqli'], true)) {
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
if ($parts === false || !in_array($parts['scheme'] ?? '', ['mysql', 'mysqli'], true)) {
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

render_nginx_config() {
    php <<'PHP'
<?php
$port = getenv('PORT') ?: '8000';
$listenDirectives = [sprintf('    listen 0.0.0.0:%s default_server;', $port)];

foreach (['8000', '80'] as $fallbackPort) {
    if ($fallbackPort !== $port) {
        $listenDirectives[] = sprintf('    listen 0.0.0.0:%s;', $fallbackPort);
    }
}

$templatePath = '/etc/nginx/sites-available/default.template';
$outputPath = '/etc/nginx/sites-available/default';
$template = file_get_contents($templatePath);

if ($template === false) {
    fwrite(STDERR, "Unable to read Nginx template.\n");
    exit(1);
}

$config = str_replace('__LISTEN_DIRECTIVES__', implode(PHP_EOL, $listenDirectives), $template);

if (file_put_contents($outputPath, $config) === false) {
    fwrite(STDERR, "Unable to write rendered Nginx config.\n");
    exit(1);
}
PHP
}

write_runtime_env_file() {
    php <<'PHP'
<?php
$envPath = '/var/www/html/.env.local';
$readEnv = static function (array $keys): string {
    foreach ($keys as $key) {
        $value = getenv($key);
        if (!is_string($value)) {
            continue;
        }

        $normalizedValue = trim($value);
        if ($normalizedValue !== '') {
            return $normalizedValue;
        }
    }

    return '';
};

$googleClientId = $readEnv(['GOOGLE_CLIENT_ID', 'GOOGLE_OAUTH_CLIENT_ID', 'OAUTH_GOOGLE_CLIENT_ID']);
$googleClientSecret = $readEnv(['GOOGLE_CLIENT_SECRET', 'GOOGLE_OAUTH_CLIENT_SECRET', 'OAUTH_GOOGLE_CLIENT_SECRET']);

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
    'GOOGLE_CLIENT_ID' => $googleClientId,
    'GOOGLE_CLIENT_SECRET' => $googleClientSecret,
    'GOOGLE_OAUTH_CLIENT_ID' => $googleClientId,
    'GOOGLE_OAUTH_CLIENT_SECRET' => $googleClientSecret,
    'OAUTH_GOOGLE_CLIENT_ID' => $googleClientId,
    'OAUTH_GOOGLE_CLIENT_SECRET' => $googleClientSecret,
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

run_with_retry() {
    label="$1"
    shift
    max_attempts="${RETRY_ATTEMPTS:-3}"
    attempt=1

    while [ "$attempt" -le "$max_attempts" ]; do
        echo "==> $label (attempt $attempt/$max_attempts)..."
        if "$@"; then
            return 0
        fi

        attempt=$((attempt + 1))
        if [ "$attempt" -le "$max_attempts" ]; then
            sleep 3
        fi
    done

    echo "==> ERROR: $label failed after $max_attempts attempts."
    return 1
}

run_console() {
    local command="cd /var/www/html && php bin/console"
    local arg

    for arg in "$@"; do
        command+=" $(printf '%q' "$arg")"
    done

    su -s /bin/bash www-data -c "$command"
}

fix_runtime_permissions() {
    chown -R www-data:www-data /var/www/html/var /var/www/html/config/jwt 2>/dev/null || true
    chmod -R 775 /var/www/html/var 2>/dev/null || true
}

ensure_schema_patches() {
    php <<'PHP'
<?php
$databaseUrl = getenv('DATABASE_URL') ?: '';
if ($databaseUrl === '') {
    exit(0);
}

$parts = parse_url($databaseUrl);
if ($parts === false || !in_array($parts['scheme'] ?? '', ['mysql', 'mysqli'], true)) {
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

    $columnExists = static function (PDO $pdo, string $table, string $column): bool {
        $statement = $pdo->query(sprintf("SHOW COLUMNS FROM `%s` LIKE '%s'", $table, str_replace("'", "''", $column)));

        return $statement !== false && $statement->fetch(PDO::FETCH_ASSOC) !== false;
    };

    $addColumnIfMissing = static function (PDO $pdo, string $table, string $column, string $definition) use ($columnExists): void {
        if ($columnExists($pdo, $table, $column)) {
            return;
        }

        $pdo->exec(sprintf('ALTER TABLE `%s` ADD `%s` %s', $table, $column, $definition));
        fwrite(STDOUT, sprintf("Added missing %s.%s column.\n", $table, $column));
    };

    $userColumns = [
        'created_by' => 'VARCHAR(100) DEFAULT NULL',
        'verified_at' => 'DATETIME DEFAULT NULL',
        'google_id' => 'VARCHAR(255) DEFAULT NULL',
        'push_token' => 'VARCHAR(255) DEFAULT NULL',
        'last_login_at' => 'DATETIME DEFAULT NULL',
        'status' => "VARCHAR(20) NOT NULL DEFAULT 'active'",
    ];

    foreach ($userColumns as $column => $definition) {
        $addColumnIfMissing($pdo, 'user', $column, $definition);
    }

    if ($columnExists($pdo, 'user', 'google_id')) {
        $indexStatement = $pdo->query("SHOW INDEX FROM `user` WHERE Column_name = 'google_id'");
        $hasGoogleIdIndex = $indexStatement !== false && $indexStatement->fetch(PDO::FETCH_ASSOC) !== false;

        if (!$hasGoogleIdIndex) {
            $pdo->exec('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON `user` (google_id)');
            fwrite(STDOUT, "Added missing user.google_id unique index.\n");
        }
    }
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}
PHP
}

if [ -z "$DATABASE_URL" ]; then
    resolved_database_url="$(resolve_database_url || true)"
    if [ -n "$resolved_database_url" ]; then
        export DATABASE_URL="$resolved_database_url"
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

echo "==> Runtime environment: APP_ENV=$APP_ENV PORT=$PORT"
if [ -n "$DATABASE_URL" ]; then
    echo "==> DATABASE_URL resolved for startup"
else
    echo "==> ERROR: DATABASE_URL is not set."
    echo "==> Connect the Railway MySQL service to this app or set DATABASE_URL manually."
    exit 1
fi

echo "==> Rendering Nginx config for PORT=$PORT..."
render_nginx_config

echo "==> Ensuring upload directories exist..."
mkdir -p /var/www/html/public/uploads/products /var/www/html/public/uploads/pets
mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/var/sessions
chown -R www-data:www-data /var/www/html/var /var/www/html/config/jwt 2>/dev/null || true
chmod -R 775 /var/www/html/var 2>/dev/null || true

echo "==> Configuring PHP-FPM to preserve runtime environment..."
if grep -Eq '^[;[:space:]]*clear_env[[:space:]]*=' /usr/local/etc/php-fpm.d/www.conf; then
    sed -i 's/^[;[:space:]]*clear_env[[:space:]]*=.*/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf
else
    printf '\nclear_env = no\n' >> /usr/local/etc/php-fpm.d/www.conf
fi

echo "==> Generating JWT keys if missing..."
if [ ! -f /var/www/html/config/jwt/private.pem ]; then
    php /var/www/html/bin/console lexik:jwt:generate-keypair --overwrite --no-interaction
    chown -R www-data:www-data /var/www/html/config/jwt 2>/dev/null || true
fi

echo "==> Starting PHP-FPM and Nginx early so Railway can reach the health check..."
php-fpm -D
nginx

set +e
wait_for_database
db_status=$?
set -e
if [ "$db_status" -ne 0 ]; then
    echo "==> ERROR: Database is not ready. Cannot continue startup."
    exit 1
fi

echo "==> Waiting for migration lock..."
set +e
acquire_migration_lock
lock_status=$?
set -e

if [ "$lock_status" -eq 0 ]; then
    echo "==> Running database migrations..."
    migration_status=0
    run_with_retry "Database migrations" run_console doctrine:migrations:migrate --no-interaction --allow-no-migration --env=prod || migration_status=$?

    echo "==> Releasing migration lock..."
    release_migration_lock || true

    if [ "$migration_status" -ne 0 ]; then
        echo "==> ERROR: Database migrations failed."
        exit 1
    fi
elif [ "$lock_status" -eq 2 ]; then
    echo "==> Another instance is running migrations. Continuing startup without local migration execution."
else
    echo "==> ERROR: Failed to acquire the migration lock."
    exit 1
fi

echo "==> Applying schema patches..."
ensure_schema_patches || {
    echo "==> ERROR: Schema patch step failed."
    exit 1
}

echo "==> Ensuring bootstrap admin accounts exist..."
run_console app:create-admin --no-interaction --env=prod || echo "==> WARNING: Bootstrap admin setup failed; continuing startup."

echo "==> Clearing and warming Symfony cache..."
run_with_retry "Cache clear" run_console cache:clear --env=prod --no-debug
run_with_retry "Cache warmup" run_console cache:warmup --env=prod --no-debug

fix_runtime_permissions

echo "==> Application startup complete. Running Nginx in the foreground..."
nginx -s quit 2>/dev/null || true
sleep 1
exec nginx -g "daemon off;"
