<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<UnusedExceptionRule>
 */
class UnusedExceptionRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new UnusedExceptionRule(new Standard());
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(
            parent::getAdditionalConfigFiles(),
            [__DIR__ . '/data/UnusedExceptionRule/unused-exception-visitor.neon'],
        );
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/UnusedExceptionRule/code.php');
    }

}
