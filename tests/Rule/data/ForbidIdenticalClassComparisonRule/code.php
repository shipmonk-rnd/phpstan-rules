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

    public function testMixed(DateTimeImmutable $a, mixed $b): void
    {
        $a === $b;
    }

    public function testAnyObject(DateTimeImmutable $a, object $b): void
    {
        $a === $b;
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
