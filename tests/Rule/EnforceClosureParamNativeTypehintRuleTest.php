<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<EnforceClosureParamNativeTypehintRule>
 */
class EnforceClosureParamNativeTypehintRuleTest extends RuleTestCase
{

    private ?bool $allowMissingTypeWhenInferred = null;

    private ?PhpVersion $phpVersion = null;

    protected function getRule(): Rule
    {
        if ($this->allowMissingTypeWhenInferred === null || $this->phpVersion === null) {
            throw new LogicException('Missing phpVersion or allowMissingTypeWhenInferred');
        }

        return new EnforceClosureParamNativeTypehintRule(
            $this->phpVersion,
            $this->allowMissingTypeWhenInferred,
        );
    }

    public function testAllowInferring(): void
    {
        $this->allowMissingTypeWhenInferred = true;
        $this->phpVersion = $this->createPhpVersion(80_000);

        $this->analyseFile(__DIR__ . '/data/EnforceClosureParamNativeTypehintRule/allow-inferring.php');
    }

    public function testEnforceEverywhere(): void
    {
        $this->allowMissingTypeWhenInferred = false;
        $this->phpVersion = $this->createPhpVersion(80_000);

        $this->analyseFile(__DIR__ . '/data/EnforceClosureParamNativeTypehintRule/enforce-everywhere.php');
    }

    public function testNoErrorOnPhp74(): void
    {
        $this->allowMissingTypeWhenInferred = false;
        $this->phpVersion = $this->createPhpVersion(70_400);

        self::assertEmpty($this->processActualErrors($this->gatherAnalyserErrors([
            __DIR__ . '/data/EnforceClosureParamNativeTypehintRule/allow-inferring.php',
            __DIR__ . '/data/EnforceClosureParamNativeTypehintRule/enforce-everywhere.php',
        ])));
    }

    private function createPhpVersion(int $version): PhpVersion
    {
        return new PhpVersion($version); // @phpstan-ignore-line ignore bc promise
    }

}
