<?php declare(strict_types = 1);

namespace EnforceEnumMatchRule;

enum SomeEnum: string
{

    case One = 'one';
    case Two = 'two';
    case Three = 'three';

    public function nonEnumConditionIncluded(bool $condition): int
    {
        if ($this === self::Three || $this === self::One) {
            return -1;
        } elseif ($this === self::Two || $condition) { // error: This condition contains always-true enum comparison of EnforceEnumMatchRule\SomeEnum::Two. Use match expression instead, PHPStan will report unhandled enum cases
            return 0;
        }

        return 1;
    }

    public function exhaustiveWithOrCondition(): int
    {
        if ($this === self::Three) {
            return -1;
        } elseif ($this === self::Two || $this === self::One) { // error: This condition contains always-true enum comparison of EnforceEnumMatchRule\SomeEnum::One. Use match expression instead, PHPStan will report unhandled enum cases
            return 0;
        }
    }

    public function basicExhaustive(): int
    {
        if ($this === self::Three) {
            return -1;
        } elseif ($this === self::Two) {
            return 0;
        } elseif ($this === self::One) { // error: This condition contains always-true enum comparison of EnforceEnumMatchRule\SomeEnum::One. Use match expression instead, PHPStan will report unhandled enum cases
            return 1;
        }
    }

    public function basicExhaustiveWithSafetyException(): int
    {
        if ($this === self::Three) {
            return -1;
        } elseif ($this === self::Two) {
            return 0;
        } elseif ($this === self::One) { // error: This condition contains always-true enum comparison of EnforceEnumMatchRule\SomeEnum::One. Use match expression instead, PHPStan will report unhandled enum cases
            return 1;
        } else {
            throw new \LogicException('cannot happen');
        }
    }

    public function notExhaustiveWithNegatedConditionInLastElseif(): int
    {
        if ($this === self::Three) {
            return -1;
        } elseif ($this === self::Two) {
            return 0;
        } elseif ($this !== self::One) { // error: This condition contains always-false enum comparison of EnforceEnumMatchRule\SomeEnum::One. Use match expression instead, PHPStan will report unhandled enum cases
            throw new \LogicException('Not expected case');
        }

        return 1;
    }

    public function nonSenseAlwaysFalseCodeNotReported(self $enum): int
    {
        if ($enum === self::Three) {
            return -1;
        }

        if ($enum === self::Three) { // cannot use $this, see https://github.com/phpstan/phpstan/issues/9142
            return 0;
        }

        return 1;
    }

    public function notExhaustive(): int
    {
        if ($this === self::Three) {
            return -1;
        } elseif ($this === self::Two) {
            return 0;
        }

        return 1;
    }

    public function exhaustiveButNoElseIf(): int
    {
        if ($this === self::Three) {
            return -1;
        }

        if ($this === self::Two) {
            return 0;
        }

        if ($this === self::One) { // error: This condition contains always-true enum comparison of EnforceEnumMatchRule\SomeEnum::One. Use match expression instead, PHPStan will report unhandled enum cases
            return 1;
        }
    }

    /**
     * @param self::Two|self::Three $param
     */
    public function exhaustiveOfSubset(self $param): int
    {
        if ($param === self::Three) {
            return -1;
        } elseif ($param === self::Two) { // error: This condition contains always-true enum comparison of EnforceEnumMatchRule\SomeEnum::Two. Use match expression instead, PHPStan will report unhandled enum cases
            return 0;
        }
    }

    public function exhaustiveButNotAllInThatElseIfChain(self $param): int
    {
        if ($param === self::One) {
            throw new \LogicException();
        }

        if ($param === self::Three) {
            return -1;
        } elseif ($param === self::Two) { // error: This condition contains always-true enum comparison of EnforceEnumMatchRule\SomeEnum::Two. Use match expression instead, PHPStan will report unhandled enum cases
            return 0;
        }
    }

    /**
     * @param true $true
     */
    public function alwaysTrueButNoEnumThere(bool $true): int
    {
        if ($this === self::Three) {
            return -1;
        } elseif ($true === true) {
            return 0;
        }
    }

}
