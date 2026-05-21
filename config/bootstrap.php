<?php

$googleEnvAliases = [
    'GOOGLE_CLIENT_ID' => 'GOOGLE_OAUTH_CLIENT_ID',
    'GOOGLE_CLIENT_SECRET' => 'GOOGLE_OAUTH_CLIENT_SECRET',
];

foreach ($googleEnvAliases as $primaryKey => $aliasKey) {
    $primaryValue = trim((string) ($_ENV[$primaryKey] ?? $_SERVER[$primaryKey] ?? ''));
    $aliasValue = trim((string) ($_ENV[$aliasKey] ?? $_SERVER[$aliasKey] ?? ''));
    $resolvedValue = $primaryValue !== '' ? $primaryValue : $aliasValue;

    if ($resolvedValue === '') {
        continue;
    }

    $_ENV[$primaryKey] = $resolvedValue;
    $_SERVER[$primaryKey] = $resolvedValue;
    $_ENV[$aliasKey] = $resolvedValue;
    $_SERVER[$aliasKey] = $resolvedValue;

    putenv(sprintf('%s=%s', $primaryKey, $resolvedValue));
    putenv(sprintf('%s=%s', $aliasKey, $resolvedValue));
}
