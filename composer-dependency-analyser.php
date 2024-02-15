<?php declare(strict_types = 1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

return $config
    ->ignoreErrorsOnPackage('phpstan/phpdoc-parser', [ErrorType::SHADOW_DEPENDENCY]); // it gets autoloaded from within the PHPStan.phar when running PHPStan
