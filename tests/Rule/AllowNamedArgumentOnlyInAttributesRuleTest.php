<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<AllowNamedArgumentOnlyInAttributesRule>
 */
class AllowNamedArgumentOnlyInAttributesRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new AllowNamedArgumentOnlyInAttributesRule();
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(
            parent::getAdditionalConfigFiles(),
            [__DIR__ . '/data/AllowNamedArgumentOnlyInAttributesRule/named-argument-visitor.neon'],
        );
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/AllowNamedArgumentOnlyInAttributesRule/code.php');
    }

}
