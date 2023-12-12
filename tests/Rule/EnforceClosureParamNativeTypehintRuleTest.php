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

    protected function getRule(): Rule
    {
        if ($this->allowMissingTypeWhenInferred === null) {
            throw new LogicException('Missing $allowMissingTypeWhenInferred');
        }

        return new EnforceClosureParamNativeTypehintRule(
            self::getContainer()->getByType(PhpVersion::class),
            $this->allowMissingTypeWhenInferred,
        );
    }

    public function testAllowInferring(): void
    {
        $this->allowMissingTypeWhenInferred = true;
        $this->analyseFile(__DIR__ . '/data/EnforceClosureParamNativeTypehintRule/allow-inferring.php');
    }

    public function testEnforceEverywhere(): void
    {
        $this->allowMissingTypeWhenInferred = false;
        $this->analyseFile(__DIR__ . '/data/EnforceClosureParamNativeTypehintRule/enforce-everywhere.php');
    }

}
