<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use ShipMonk\PHPStan\RuleTestCase;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;

/**
 * @extends RuleTestCase<EnforceNativeReturnTypehintRule>
 */
class EnforceNativeReturnTypehintRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        return new EnforceNativeReturnTypehintRule(
            self::getContainer()->getByType(FileTypeMapper::class),
            self::getContainer()->getByType(PhpVersion::class),
            true,
        );
    }

    public function testPhp82(): void
    {
        if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 2) {
            self::markTestSkipped('Test for PHP 8.2');
        }

        $this->analyseFile(__DIR__ . '/data/EnforceNativeReturnTypehintRule/code-82.php');
    }

    public function testPhp81(): void
    {
        if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 1) {
            self::markTestSkipped('Test for PHP 8.1');
        }

        $this->analyseFile(__DIR__ . '/data/EnforceNativeReturnTypehintRule/code-81.php');
    }

    public function testPhp80(): void
    {
        if (PHP_MAJOR_VERSION !== 8 || PHP_MINOR_VERSION !== 0) {
            self::markTestSkipped('Test for PHP 8.0');
        }

        $this->analyseFile(__DIR__ . '/data/EnforceNativeReturnTypehintRule/code-80.php');
    }

    public function testPhp74(): void
    {
        if (PHP_MAJOR_VERSION !== 7 || PHP_MINOR_VERSION !== 4) {
            self::markTestSkipped('Test for PHP 7.4');
        }

        $this->analyseFile(__DIR__ . '/data/EnforceNativeReturnTypehintRule/code-74.php');
    }

}
