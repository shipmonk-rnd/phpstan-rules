<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use ShipMonk\PHPStan\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<EnforceNativeReturnTypehintRule>
 */
class EnforceNativeReturnTypehintRuleTest extends RuleTestCase
{

    private ?PhpVersion $phpVersion = null;

    protected function getRule(): Rule
    {
        self::assertNotNull($this->phpVersion);

        return new EnforceNativeReturnTypehintRule(
            self::getContainer()->getByType(FileTypeMapper::class),
            $this->phpVersion,
            true,
            true,
        );
    }

    public function testEnum(): void
    {
        if (PHP_VERSION_ID < 80_100) {
            self::markTestSkipped('Requires PHP 8.1');
        }

        $this->phpVersion = self::getContainer()->getByType(PhpVersion::class);
        $this->analyseFile(__DIR__ . '/data/EnforceNativeReturnTypehintRule/code-enum.php');
    }

    public function testPhp82(): void
    {
        $this->phpVersion = $this->createPhpVersion(80_200);
        $this->analyseFile(__DIR__ . '/data/EnforceNativeReturnTypehintRule/code-82.php');
    }

    public function testPhp81(): void
    {
        $this->phpVersion = $this->createPhpVersion(80_100);
        $this->analyseFile(__DIR__ . '/data/EnforceNativeReturnTypehintRule/code-81.php');
    }

    public function testPhp80(): void
    {
        $this->phpVersion = $this->createPhpVersion(80_000);
        $this->analyseFile(__DIR__ . '/data/EnforceNativeReturnTypehintRule/code-80.php');
    }

    public function testPhp74(): void
    {
        $this->phpVersion = $this->createPhpVersion(70_400);
        $this->analyseFile(__DIR__ . '/data/EnforceNativeReturnTypehintRule/code-74.php');
    }

    private function createPhpVersion(int $version): PhpVersion
    {
        return new PhpVersion($version); // @phpstan-ignore-line ignore bc promise
    }

}
