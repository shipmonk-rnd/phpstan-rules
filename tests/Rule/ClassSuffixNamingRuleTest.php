<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ClassSuffixNamingRule>
 */
class ClassSuffixNamingRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ClassSuffixNamingRule(
            self::getContainer()->getByType(ReflectionProvider::class),
            [
                'ClassSuffixNamingRule\CheckedParent' => 'Suffix',
                'ClassSuffixNamingRule\CheckedInterface' => 'Suffix2',
            ],
        );
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ClassSuffixNamingRule/code.php');
    }

}
