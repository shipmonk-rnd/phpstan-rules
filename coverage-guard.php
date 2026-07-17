<?php

use ShipMonk\CoverageGuard\Config;
use ShipMonk\CoverageGuard\Excluder\IgnoreThrowNewExceptionLineExcluder;
use ShipMonk\CoverageGuard\Rule\EnforceCoverageForMethodsRule;

$config = new Config();
$config->addRule(new EnforceCoverageForMethodsRule(
    requiredCoveragePercentage: 70,
    minExecutableLines: 5,
));
$config->addExecutableLineExcluder(new IgnoreThrowNewExceptionLineExcluder([LogicException::class]));

return $config;
