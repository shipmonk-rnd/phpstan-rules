<?php declare(strict_types = 1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

$pharFile = __DIR__ . '/vendor/phpstan/phpstan/phpstan.phar';
Phar::loadPhar($pharFile, 'phpstan.phar');

require_once('phar://phpstan.phar/preload.php'); // prepends PHPStan's PharAutolaoder to composer's autoloader

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests/Rule/data');
