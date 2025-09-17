<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ForbidArithmeticOperationOnNonNumberRule>
 */
class ForbidArithmeticOperationOnNonNumberRuleTest extends RuleTestCase
{

    private ?bool $allowNumericString = null;

    protected function getRule(): Rule
    {
        if ($this->allowNumericString === null) {
            throw new LogicException('allowNumericString must be set');
        }

        return new ForbidArithmeticOperationOnNonNumberRule($this->allowNumericString);
    }

    public function test(): void
    {
        $this->allowNumericString = true;
        $this->analyseFile(__DIR__ . '/data/ForbidArithmeticOperationOnNonNumberRule/code.php');
    }

    public function testNoNumericString(): void
    {
        $this->allowNumericString = false;
        $this->analyseFile(__DIR__ . '/data/ForbidArithmeticOperationOnNonNumberRule/no-numeric-string.php');
    }

    public function testBcMathNumber(): void
    {
        if (PHP_VERSION_ID < 80_400) {
            self::markTestSkipped('Requires PHP 8.4');
        }

        $this->allowNumericString = true;
        $this->analyseFile(__DIR__ . '/data/ForbidArithmeticOperationOnNonNumberRule/bcmath-number.php');
    }

    public function testBcMathNumberNoNumeric(): void
    {
        if (PHP_VERSION_ID < 80_400) {
            self::markTestSkipped('Requires PHP 8.4');
        }

        $this->allowNumericString = false;
        $this->analyseFile(__DIR__ . '/data/ForbidArithmeticOperationOnNonNumberRule/bcmath-number-no-numeric.php');
    }

    protected function shouldFailOnPhpErrors(): bool
    {
        return false; // https://github.com/phpstan/phpstan-src/pull/3031
    }

}
