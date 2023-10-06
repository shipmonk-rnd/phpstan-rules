<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use Nette\Neon\Neon;
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

        $visitorConfig = Neon::decodeFile(self::getVisitorConfigFilePath());

        return new ForbidCheckedExceptionInCallableRule(
            self::getContainer()->getByType(NodeScopeResolver::class),
            self::getContainer()->getByType(ReflectionProvider::class),
            new DefaultExceptionTypeResolver( // @phpstan-ignore-line ignore BC promise
                self::getContainer()->getByType(ReflectionProvider::class),
                [],
                [],
                [],
                $this->checkedExceptions, // everything is checked when no config is provided
            ),
            $visitorConfig['services'][0]['arguments']['immediatelyCalledCallables'], // @phpstan-ignore-line ignore mixed access
            $visitorConfig['services'][0]['arguments']['allowedCheckedExceptionCallables'], // @phpstan-ignore-line ignore mixed access
        );
    }

    /**
     * @param list<string> $checkedExceptions
     * @dataProvider provideSetup
     */
    public function test(bool $implicitThrows, array $checkedExceptions): void
    {
        self::$implicitThrows = $implicitThrows;
        $this->checkedExceptions = $checkedExceptions;

        $this->analyseFile(__DIR__ . '/data/ForbidCheckedExceptionInCallableRule/code.php');
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
        $files = [
            self::getVisitorConfigFilePath(),
        ];

        if (self::$implicitThrows === true) {
            $files[] = __DIR__ . '/data/ForbidCheckedExceptionInCallableRule/no-implicit-throws.neon';
        }

        return $files;
    }

    private static function getVisitorConfigFilePath(): string
    {
        return __DIR__ . '/data/ForbidCheckedExceptionInCallableRule/visitor.neon';
    }

}
