<?php declare(strict_types = 1);

$readmeContents = file_get_contents(__DIR__ . '/../README.md');
$configContents = file_get_contents(__DIR__ . '/../rules.neon');
$defaultConfig = trim(explode("parametersSchema", $configContents)[0]);

if (!str_contains($readmeContents, $defaultConfig)) {
    echo "README.md does not contain default config used in rules.neon\n";
    exit(1);
}

echo "README default config matches reality\n";
