<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<BackedEnumGenericsRule>
 */
class BackedEnumGenericsRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new BackedEnumGenericsRule();
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(
            parent::getAdditionalConfigFiles(),
            [__DIR__ . '/data/BackedEnumGenericsRule/stub.neon'],
        );
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/BackedEnumGenericsRule/code.php');
    }

}
