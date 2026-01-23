<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Exceptions\DefaultExceptionTypeResolver;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidCheckedExceptionInCallableRule>
 */
class ForbidCheckedExceptionInCallableRuleTest extends RuleTestCase
{

    private static ?bool $implicitThrows = null;

    /**
     * @var list<string>|null
     */
    private ?array $checkedExceptions = null;

    protected function getRule(): Rule
    {
        if ($this->checkedExceptions === null) {
            throw new LogicException('Missing checkedExceptions');
        }

        if (self::$implicitThrows === null) {
            throw new LogicException('Missing implicitThrows');
        }

        return new ForbidCheckedExceptionInCallableRule(
            self::getContainer()->getByType(NodeScopeResolver::class),
            self::getContainer()->getByType(ReflectionProvider::class),
            new DefaultExceptionTypeResolver( // @phpstan-ignore phpstanApi.constructor
                self::getContainer()->getByType(ReflectionProvider::class),
                [],
                [],
                [],
                $this->checkedExceptions, // everything is checked when no config is provided
            ),
            [
                'ForbidCheckedExceptionInCallableRule\CallableTest::allowThrowInInterface' => [0],
                'ForbidCheckedExceptionInCallableRule\BaseCallableTest::allowThrowInBaseClass' => [0],
                'ForbidCheckedExceptionInCallableRule\ClosureTest::allowThrow' => [0],
                'ForbidCheckedExceptionInCallableRule\FirstClassCallableTest::allowThrow' => [1],
                'ForbidCheckedExceptionInCallableRule\ArrowFunctionTest::allowThrow' => [0],
                'ForbidCheckedExceptionInCallableRule\ArrowFunctionTest::__construct' => [0],
                'ForbidCheckedExceptionInCallableRule\allowed_function' => [0], // not really needed as functions are always considered immediately invoked (https://phpstan.org/writing-php-code/phpdocs-basics#callables)
                'ForbidCheckedExceptionInCallableRule\allowed_function_not_immediate' => [0],
            ],
        );
    }

    /**
     * @param list<string> $checkedExceptions
     *
     * @dataProvider provideSetup
     */
    public function test(
        bool $implicitThrows,
        array $checkedExceptions
    ): void
    {
        self::$implicitThrows = $implicitThrows;
        $this->checkedExceptions = $checkedExceptions;

        $this->analyseFile(__DIR__ . '/data/ForbidCheckedExceptionInCallableRule/code.php');
    }

    public function testTrait(): void
    {
        self::$implicitThrows = false;
        $this->checkedExceptions = ['Exception'];
        $this->analyseFile([
            __DIR__ . '/data/ForbidCheckedExceptionInCallableRule/code-for-trait.php',
            __DIR__ . '/data/ForbidCheckedExceptionInCallableRule/trait.php',
        ]);
    }

    /**
     * @return iterable<mixed>
     */
    public function provideSetup(): iterable
    {
        yield ['implicitThrows' => true, 'checkedExceptions' => []];
        yield ['implicitThrows' => true, 'checkedExceptions' => ['ForbidCheckedExceptionInCallableRule\CheckedException']];
        yield ['implicitThrows' => false, 'checkedExceptions' => []];
        yield ['implicitThrows' => false, 'checkedExceptions' => ['ForbidCheckedExceptionInCallableRule\CheckedException']];
    }

    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        $files = [];

        if (self::$implicitThrows === true) {
            $files[] = __DIR__ . '/data/ForbidCheckedExceptionInCallableRule/no-implicit-throws.neon';
        }

        return $files;
    }

}
