<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidNullInInterpolatedStringRule>
 */
class ForbidNullInInterpolatedStringRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidNullInInterpolatedStringRule(
            self::getContainer()->getByType(Standard::class),
        );
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidNullInInterpolatedStringRule/code.php');
    }

}
