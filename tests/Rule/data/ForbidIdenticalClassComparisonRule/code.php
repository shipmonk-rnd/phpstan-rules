<?php declare(strict_types = 1);

namespace ForbidIdenticalClassComparisonRule;

use DateTimeImmutable;

class DateTimeImmutableChild extends DateTimeImmutable {}

class Dummy {}

interface DummyInterface {}

class A
{

    public function testNonObject(?DateTimeImmutable $a, string $b): void
    {
        $a === $a;
        $a === $b;

        if ($a !== null) {
            $a->modify($b) === false;
        }
    }

    /**
     * @param TItem|null $mixedTemplate1
     * @param TItem|null $mixedTemplate2
     * @param callable(DateTimeImmutable): void $callable1
     * @param callable(DateTimeImmutable): void $callable2
     *
     * @template TItem
     */
    public function testProblematicTypes(
        DateTimeImmutable $a,
        mixed $b,
        object $c,
        callable $d,
        mixed $mixedTemplate1,
        mixed $mixedTemplate2,
        callable $callable1,
        callable $callable2
    ): void
    {
        $a === $b;
        $a === $c;
        $a === $d;
        $mixedTemplate1 === $mixedTemplate2;
        $callable1 === $callable2;
    }

    public function testRegular(DateTimeImmutable $a, DateTimeImmutable $b): void
    {
        $a === $b; // error: Using === with DateTimeInterface is denied
        $a !== $b; // error: Using !== with DateTimeInterface is denied
    }

    public function testNullable(?DateTimeImmutable $a, DateTimeImmutable $b): void
    {
        $a === $b; // error: Using === with DateTimeInterface is denied
    }

    /**
     * @param DateTimeImmutable|Dummy $a
     * @param DateTimeImmutable|Dummy $b
     */
    public function testUnion(object $a, object $b, Dummy $c, DateTimeImmutable $d): void
    {
        $a === $b; // error: Using === with DateTimeInterface is denied
        $a === $d; // error: Using === with DateTimeInterface is denied
        $a === $c;
    }

    public function testChild(DateTimeImmutableChild $a, ?DateTimeImmutable $b): void
    {
        $a === $b; // error: Using === with DateTimeInterface is denied
    }

    /**
     * @param DateTimeImmutable&DummyInterface $a
     */
    public function testIntersection(object $a, DateTimeImmutable $b): void
    {
        $a === $b; // error: Using === with DateTimeInterface is denied
    }

}
