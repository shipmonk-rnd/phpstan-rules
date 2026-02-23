<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ForbidIncrementDecrementOnNonIntegerRule>
 */
class ForbidIncrementDecrementOnNonIntegerRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidIncrementDecrementOnNonIntegerRule(
            self::getContainer()->getByType(PhpVersion::class),
        );
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidIncrementDecrementOnNonIntegerRule/code.php');
    }

    public function testBcMathNumber(): void
    {
        if (PHP_VERSION_ID < 80_400) {
            self::markTestSkipped('Requires PHP 8.4');
        }

        $this->analyseFile(__DIR__ . '/data/ForbidIncrementDecrementOnNonIntegerRule/bcmath-number.php');
    }

}
