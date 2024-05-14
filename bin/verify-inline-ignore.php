<?php declare(strict_types = 1);

$error = false;
$paths = [
    __DIR__ . '/../src',
    __DIR__ . '/../tests',
];

foreach ($paths as $path) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

    foreach ($iterator as $entry) {
        /** @var DirectoryIterator $entry */
        if (!$entry->isFile() || $entry->getExtension() !== 'php') {
            continue;
        }

        $realpath = realpath($entry->getPathname());
        $contents = file_get_contents($realpath);
        if (strpos($contents, '@phpstan-ignore-') !== false) {
            $error = true;
            echo $realpath . ' uses @phpstan-ignore-line, use \'@phpstan-ignore identifier (reason)\' instead' . PHP_EOL;
        }
    }
}

if ($error) {
    exit(1);
} else {
    echo 'Ok, no non-identifier based inline ignore found' . PHP_EOL;
    exit(0);
}

