<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidPhpDocNullabilityMismatchWithNativeTypehintRule>
 */
class ForbidPhpDocNullabilityMismatchWithNativeTypehintRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidPhpDocNullabilityMismatchWithNativeTypehintRule(
            self::getContainer()->getByType(FileTypeMapper::class),
        );
    }

    public function testBasic(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidPhpDocNullabilityMismatchWithNativeTypehintRule/code.php');
    }

}
