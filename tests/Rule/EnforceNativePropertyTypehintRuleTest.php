<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use ShipMonk\PHPStan\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<EnforceNativePropertyTypehintRule>
 */
class EnforceNativePropertyTypehintRuleTest extends RuleTestCase
{

    private ?PhpVersion $phpVersion = null;

    protected function getRule(): Rule
    {
        self::assertNotNull($this->phpVersion);

        return new EnforceNativePropertyTypehintRule(
            self::getContainer()->getByType(FileTypeMapper::class),
            $this->phpVersion,
            true,
        );
    }

    public function testPhp74(): void
    {
        $this->phpVersion = $this->createPhpVersion(70_400);
        $this->analyseFile(__DIR__ . '/data/EnforceNativePropertyTypehintRule/code-74.php');
    }

    public function testPhp80(): void
    {
        $this->phpVersion = $this->createPhpVersion(80_000);
        $this->analyseFile(__DIR__ . '/data/EnforceNativePropertyTypehintRule/code-80.php');
    }

    public function testPhp81(): void
    {
        if (PHP_VERSION_ID < 80_100) {
            self::markTestSkipped('Requires PHP 8.1');
        }

        $this->phpVersion = $this->createPhpVersion(80_100);
        $this->analyseFile(__DIR__ . '/data/EnforceNativePropertyTypehintRule/code-81.php');
    }

    public function testPhp82(): void
    {
        if (PHP_VERSION_ID < 80_100) {
            self::markTestSkipped('Requires PHP 8.1');
        }

        $this->phpVersion = $this->createPhpVersion(80_200);
        $this->analyseFile(__DIR__ . '/data/EnforceNativePropertyTypehintRule/code-82.php');
    }

    public function testParent(): void
    {
        $this->phpVersion = $this->createPhpVersion(80_000);
        $this->analyseFile(__DIR__ . '/data/EnforceNativePropertyTypehintRule/code-parent.php');
    }

    private function createPhpVersion(int $version): PhpVersion
    {
        return new PhpVersion($version);
    }

}
