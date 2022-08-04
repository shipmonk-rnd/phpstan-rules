<?php declare(strict_types = 1);

$config = [];

// https://github.com/phpstan/phpstan/issues/6290
if (PHP_VERSION_ID < 80_000) {
    $config['parameters']['ignoreErrors'][] = '~Class BackedEnum not found.~';
}

return $config;
