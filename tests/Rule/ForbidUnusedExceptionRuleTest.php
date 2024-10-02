<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<ForbidUnusedExceptionRule>
 */
class ForbidUnusedExceptionRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidUnusedExceptionRule(
            self::getContainer()->getByType(Printer::class), // @phpstan-ignore phpstanApi.classConstant
        );
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(
            parent::getAdditionalConfigFiles(),
            [__DIR__ . '/data/ForbidUnusedExceptionRule/unused-exception-visitor.neon'],
        );
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidUnusedExceptionRule/code.php');
    }

}
