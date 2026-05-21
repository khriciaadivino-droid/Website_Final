<?php

$readEnv = static function (array $keys): string {
    foreach ($keys as $key) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

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

$writeEnv = static function (string $key, string $value): void {
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
    putenv(sprintf('%s=%s', $key, $value));
};

foreach (
    [
        ['GOOGLE_CLIENT_ID', 'GOOGLE_OAUTH_CLIENT_ID', 'OAUTH_GOOGLE_CLIENT_ID'],
        ['GOOGLE_CLIENT_SECRET', 'GOOGLE_OAUTH_CLIENT_SECRET', 'OAUTH_GOOGLE_CLIENT_SECRET'],
    ] as $aliasGroup
) {
    $resolvedValue = $readEnv($aliasGroup);

    if ($resolvedValue === '') {
        continue;
    }

    foreach ($aliasGroup as $key) {
        $writeEnv($key, $resolvedValue);
    }
}
