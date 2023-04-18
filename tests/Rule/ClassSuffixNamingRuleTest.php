<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ClassSuffixNamingRule>
 */
class ClassSuffixNamingRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ClassSuffixNamingRule([ // @phpstan-ignore-line ignore non existing class not being class-string
            'ClassSuffixNamingRule\CheckedParent' => 'Suffix',
            'ClassSuffixNamingRule\CheckedInterface' => 'Suffix2',
            'NotExistingClass' => 'Foo',
        ]);
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ClassSuffixNamingRule/code.php');
    }

}
