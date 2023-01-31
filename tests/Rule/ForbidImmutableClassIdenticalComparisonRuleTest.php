<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidImmutableClassIdenticalComparisonRule>
 */
class ForbidImmutableClassIdenticalComparisonRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidImmutableClassIdenticalComparisonRule(
            self::getContainer()->getByType(ReflectionProvider::class),
        );
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidImmutableClassIdenticalComparisonRule/code.php');
    }

}
