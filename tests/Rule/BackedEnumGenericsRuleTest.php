<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use function array_merge;
use const PHP_VERSION_ID;

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
        if (PHP_VERSION_ID < 80_100) {
            self::markTestSkipped('Requires PHP 8.1');
        }

        $this->analyseFile(__DIR__ . '/data/BackedEnumGenericsRule/code.php');
    }

}
