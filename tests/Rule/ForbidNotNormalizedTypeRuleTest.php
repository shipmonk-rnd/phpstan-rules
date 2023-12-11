<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\PrettyPrinter\Standard;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\File\FileHelper;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
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
            new FileTypeMapper( // @phpstan-ignore-line
                self::getContainer()->getByType(ReflectionProviderProvider::class), // @phpstan-ignore-line
                self::getContainer()->getService('currentPhpVersionRichParser'), // @phpstan-ignore-line
                new PhpDocStringResolver( // @phpstan-ignore-line
                    new Lexer(),
                    new PhpDocParser(
                        self::getContainer()->getByType(TypeParser::class),
                        self::getContainer()->getByType(ConstExprParser::class),
                        false,
                        false,
                        ['lines' => true], // simplify after https://github.com/phpstan/phpstan-src/pull/2807
                    ),
                ),
                self::getContainer()->getByType(PhpDocNodeResolver::class), // @phpstan-ignore-line
                self::getContainer()->getByType(AnonymousClassNameHelper::class), // @phpstan-ignore-line
                self::getContainer()->getByType(FileHelper::class),
            ),
            self::getContainer()->getByType(TypeNodeResolver::class),
            self::getContainer()->getByType(Standard::class),
        );
    }

    public function testRule(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidNotNormalizedTypeRule/code.php');
    }

}
