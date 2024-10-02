<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Node\Printer\Printer;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\Type\FileTypeMapper;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidNotNormalizedTypeRule>
 */
class ForbidNotNormalizedTypeRuleTest extends RuleTestCase
{

    protected function getRule(): ForbidNotNormalizedTypeRule
    {
        return new ForbidNotNormalizedTypeRule(
            self::getContainer()->getByType(FileTypeMapper::class),
            self::getContainer()->getByType(TypeNodeResolver::class),
            self::getContainer()->getByType(Printer::class),
            true,
        );
    }

    public function testRule(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidNotNormalizedTypeRule/code.php');
    }

}
