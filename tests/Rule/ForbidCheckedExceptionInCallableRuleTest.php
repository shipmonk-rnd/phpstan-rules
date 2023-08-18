<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use Nette\Neon\Neon;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Exceptions\DefaultExceptionTypeResolver;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidCheckedExceptionInCallableRule>
 */
class ForbidCheckedExceptionInCallableRuleTest extends RuleTestCase
{

    /**
     * @var list<string>|null
     */
    private ?array $checkedExceptions = null;

    protected function getRule(): Rule
    {
        if ($this->checkedExceptions === null) {
            throw new LogicException('Missing checkedExceptions');
        }

        $visitorConfig = Neon::decodeFile(self::getVisitorConfigFilePath());

        return new ForbidCheckedExceptionInCallableRule(
            self::getContainer()->getByType(ReflectionProvider::class),
            new DefaultExceptionTypeResolver( // @phpstan-ignore-line ignore BC promise
                self::getContainer()->getByType(ReflectionProvider::class),
                [],
                [],
                [],
                $this->checkedExceptions,
            ),
            $visitorConfig['services'][0]['arguments']['immediatelyCalledCallables'], // @phpstan-ignore-line ignore mixed access
            $visitorConfig['services'][0]['arguments']['allowedCheckedExceptionCallables'], // @phpstan-ignore-line ignore mixed access
        );
    }

    public function testImplicit(): void
    {
        $this->checkedExceptions = [];
        $this->analyseFile(__DIR__ . '/data/ForbidCheckedExceptionInCallableRule/code.php', ['implicit']);
    }

    public function testExplicit(): void
    {
        $this->checkedExceptions = ['ForbidCheckedExceptionInCallableRule\CheckedException'];
        $this->analyseFile(__DIR__ . '/data/ForbidCheckedExceptionInCallableRule/code.php');
    }

    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            self::getVisitorConfigFilePath(),
        ];
    }

    private static function getVisitorConfigFilePath(): string
    {
        return __DIR__ . '/data/ForbidCheckedExceptionInCallableRule/visitor.neon';
    }

}
