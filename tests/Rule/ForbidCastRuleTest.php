<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ForbidCastRule>
 */
class ForbidCastRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new ForbidCastRule();
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidCastRule/code.php');
    }

    public function testClassPhp85(): void
    {
        if (PHP_VERSION_ID < 80_500) {
            self::markTestSkipped('Requires PHP 8.5');
        }

        $this->phpVersion = $this->createPhpVersion(80_500);
        $this->analyseFile(__DIR__ . '/data/ForbidCastRule/code-85.php');
    }

    private function createPhpVersion(int $version): PhpVersion
    {
        return new PhpVersion($version);
    }

}
