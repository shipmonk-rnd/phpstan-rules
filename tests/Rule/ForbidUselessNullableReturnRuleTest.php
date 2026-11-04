<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<ForbidUselessNullableReturnRule>
 */
class ForbidUselessNullableReturnRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidUselessNullableReturnRule();
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(
            parent::getAdditionalConfigFiles(),
            [__DIR__ . '/data/ForbidUselessNullableReturnRule/return-type-node-visitor.neon'],
        );
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidUselessNullableReturnRule/code.php');
    }

    public function testPropertyHooks(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidUselessNullableReturnRule/code-hook.php');
    }

    public function testFix(): void
    {
        $this->fix(__DIR__ . '/data/ForbidUselessNullableReturnRule/fix.php', __DIR__ . '/data/ForbidUselessNullableReturnRule/fix.php.fixed');
    }

}
