<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PHPStan\Rules\Exceptions\DefaultExceptionTypeResolver;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<ForbidCheckedExceptionInYieldingMethodRule>
 */
class ForbidCheckedExceptionInYieldingMethodRuleTest extends RuleTestCase
{

    protected function getRule(): Rule
    {
        $exceptionTypeResolverMock = self::createMock(DefaultExceptionTypeResolver::class);
        $exceptionTypeResolverMock
            ->expects(self::any())
            ->method('isCheckedException')
            ->willReturnCallback(static function (string $className): bool {
                return $className === 'ForbidCheckedExceptionInYieldingMethodRule\CheckedException';
            });

        return new ForbidCheckedExceptionInYieldingMethodRule($exceptionTypeResolverMock);
    }

    public function testClass(): void
    {
        $this->analyseFile(__DIR__ . '/data/ForbidCheckedExceptionInYieldingMethodRule/code.php');
    }

}
