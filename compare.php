#!/usr/bin/env php
<?php

// usage: vendor/bin/phpstan analyse -v -c compare.phpstan.neon --error-format=prettyJson tests/Rule/data/ForbidCustomFunctionsRule/code.php | php compare.php

$errors = json_decode(file_get_contents("php://stdin"), true);

function transformMessage(string $message): string
{
    $replace = [
        'Calling ' => 'Method ',
        'is forbidden,' => 'is forbidden.',
    ];

    return str_replace(array_keys($replace), array_values($replace), rtrim($message, '.'));
}

$iterator = new DirectoryIterator(__DIR__ . '/tests/Rule/data/ForbidCustomFunctionsRule');

foreach ($iterator as $fileinfo) {
    if (!$fileinfo->isFile() || $fileinfo->getExtension() !== 'php') {
        continue;
    }
    $filePath = $fileinfo->getPathname();

    $contents = file_get_contents($filePath);
    $contentsLines = explode("\n", $contents);

    foreach ($contentsLines as $line => $row) {
        $newLine = preg_replace('~ ?// error.*$~', '', $row);
        $contentsLines[$line] = $newLine;
    }

    foreach ($errors['files'][$filePath]['messages'] ?? [] as $error) {
        $line = $error['line'];
        $contentsLines[$line - 1] .= ' // error: ' . transformMessage($error['message']);
    }

    file_put_contents($filePath, implode("\n", $contentsLines));
}
