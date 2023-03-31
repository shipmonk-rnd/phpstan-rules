<?php declare(strict_types = 1);

namespace EnforceEnumMatchRule;


enum AnotherEnum: string
{
    case Foo = 'foo';
    case Bar = 'bar';
}

enum SomeEnum: string
{

    case Dom = 'dom';
    case Int = 'int';
    case Out = 'out';

    public function nonEnumConditionIncluded(bool $condition): int
    {
        if ($this === self::Out) {
            return -1;
        } elseif ($this === self::Int || $condition) {
            return 0;
        }

        return 1;
    }

    public function exhaustiveWithOrCondition(): int
    {
        if ($this === self::Out) {
            return -1;
        } elseif ($this === self::Int || $this === self::Dom) { // not detected (reported as always-true in phpstan)
            return 0;
        }
    }

    public function basicExhaustive(): int
    {
        if ($this === self::Out) {
            return -1;
        } elseif ($this === self::Int) {
            return 0;
        } elseif ($this === self::Dom) { // error: This else-if chain looks like exhaustive enum check. Rewrite it to match construct to ensure that error is raised when new enum case is added.
            return 1;
        }
    }

    public function notExhaustiveWithNegatedConditionInLastElseif(): int
    {
        if ($this === self::Out) {
            return -1;
        } elseif ($this === self::Int) {
            return 0;
        } elseif ($this !== self::Dom) {  // this one is reported as always false in native phpstan
            throw new \LogicException('Not expected case');
        }

        return 1;
    }

    public function notExhaustive(): int
    {
        if ($this === self::Out) {
            return -1;
        } elseif ($this === self::Int) {
            return 0;
        }

        return 1;
    }

    public function exhaustiveButNoElseIf(): int
    {
        if ($this === self::Out) {
            return -1;
        }

        if ($this === self::Int) {
            return 0;
        }

        if ($this === self::Dom) { // this one is reported as always true in native phpstan
            return 1;
        }
    }

    /**
     * @param self::Out|self::Int $param
     */
    public function exhaustiveOfSubset(self $param): int
    {
        if ($param === self::Out) {
            return -1;
        } elseif ($param === self::Int) { // error: This else-if chain looks like exhaustive enum check. Rewrite it to match construct to ensure that error is raised when new enum case is added.
            return 0;
        }
    }

    public function exhaustiveButNotAllInThatElseIfChain(self $param): int
    {
        if ($param === self::Dom) {
            throw new \LogicException();
        }

        if ($param === self::Out) {
            return -1;
        } elseif ($param === self::Int) { // error: This else-if chain looks like exhaustive enum check. Rewrite it to match construct to ensure that error is raised when new enum case is added.
            return 0;
        }
    }

    /**
     * @param true $true
     */
    public function alwaysTrueButNoEnumThere(bool $true): int
    {
        if ($this === self::Out) {
            return -1;
        } elseif ($true === true) {
            return 0;
        }
    }

}
