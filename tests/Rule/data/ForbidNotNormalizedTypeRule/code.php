<?php declare(strict_types=1);

namespace ForbidNotNormalizedTypeRule;

interface MyInterface {}

abstract class BaseClass  {}
final class ChildOne extends BaseClass {}
class InterfaceImplementor implements MyInterface {}


interface I {}
class A {}
class B {}

class Example
{

    /**
     * @var ChildOne|BaseClass $a  // error: Found non-normalized type (ChildOne | BaseClass) for variable $a: ChildOne is a subtype of BaseClass.
     * @var mixed|BaseClass $b     // error: Found non-normalized type (mixed | BaseClass) for variable $b: BaseClass is a subtype of mixed.
     */
    public $a, $b;

    /**
     * @var mixed|null // error: Found non-normalized type (mixed | null): null is a subtype of mixed.
     */
    public $c;

    /**
     * @var I&(A|B) // error: Found non-normalized type (I & (A | B)): this is not disjunctive normal form, use (ForbidNotNormalizedTypeRule\A&ForbidNotNormalizedTypeRule\I)|(ForbidNotNormalizedTypeRule\B&ForbidNotNormalizedTypeRule\I)
     */
    public $notDnf;



    public ChildOne|BaseClass $i, $j; // error: Found non-normalized type \ForbidNotNormalizedTypeRule\ChildOne|\ForbidNotNormalizedTypeRule\BaseClass for property $i,j: \ForbidNotNormalizedTypeRule\ChildOne is a subtype of \ForbidNotNormalizedTypeRule\BaseClass.
    public mixed|null $k; // error: Found non-normalized type mixed|null for property $k: null is a subtype of mixed.


    /**
     * @return ChildOne|BaseClass  // error: Found non-normalized type (ChildOne | BaseClass) for return: ChildOne is a subtype of BaseClass.
     */
    public function testReturn1(
        mixed $mixed,
    ): object
    {
    }

    /**
     * @return ChildOne|BaseClass  // error: Found non-normalized type (ChildOne | BaseClass) for return: ChildOne is a subtype of BaseClass.
     */
    public function testReturn2(mixed $mixed)
    {
    }

    public function testNativeReturn(mixed $mixed): ChildOne|BaseClass // error: Found non-normalized type \ForbidNotNormalizedTypeRule\ChildOne|\ForbidNotNormalizedTypeRule\BaseClass for return: \ForbidNotNormalizedTypeRule\ChildOne is a subtype of \ForbidNotNormalizedTypeRule\BaseClass.
    {
    }

    public function testInlineVarDoc($mixed) {
        /** @var mixed|null $a */  // error: Found non-normalized type (mixed | null) for variable $a: null is a subtype of mixed.
        $a = $mixed;
    }

    /**
     * @param ChildOne|BaseClass $a                 // error: Found non-normalized type (ChildOne | BaseClass) for parameter $a: ChildOne is a subtype of BaseClass.
     * @param mixed|null $b                         // error: Found non-normalized type (mixed | null) for parameter $b: null is a subtype of mixed.
     * @param int|positive-int $c                   // error: Found non-normalized type (int | positive-int) for parameter $c: positive-int is a subtype of int.
     * @param int[]|array<int> $d                   // error: Found non-normalized type (int[] | array<int>) for parameter $d: array<int> is a subtype of int[].
     * @param ?mixed $e                             // error: Found non-normalized type (mixed | null) for parameter $e: null is a subtype of mixed.
     * @param array<mixed|int> $f                   // error: Found non-normalized type (mixed | int) for parameter $f: int is a subtype of mixed.
     * @param list<mixed|int> $g                    // error: Found non-normalized type (mixed | int) for parameter $g: int is a subtype of mixed.
     * @param list<int>|array<int> $h               // error: Found non-normalized type (list<int> | array<int>) for parameter $h: list<int> is a subtype of array<int>.
     * @param ChildOne|MyInterface $i
     * @param InterfaceImplementor|MyInterface $j   // error: Found non-normalized type (InterfaceImplementor | MyInterface) for parameter $j: InterfaceImplementor is a subtype of MyInterface.
     * @param callable(mixed|null): mixed $k        // error: Found non-normalized type (mixed | null) for parameter $k: null is a subtype of mixed.
     * @param callable(): (mixed|null) $l           // error: Found non-normalized type (mixed | null) for parameter $l: null is a subtype of mixed.
     */
    public function testPhpDocUnions($a, $b, $c, $d, $e, $f, $g, $h, $i, $j, $k, $l): void
    {
    }


    /**
     * @param ChildOne&BaseClass $a               // error: Found non-normalized type (ChildOne & BaseClass) for parameter $a: ChildOne is a subtype of BaseClass.
     * @param mixed&null $b                       // error: Found non-normalized type (mixed & null) for parameter $b: null is a subtype of mixed.
     * @param int&positive-int $c                 // error: Found non-normalized type (int & positive-int) for parameter $c: positive-int is a subtype of int.
     * @param int[]&array<int> $d                 // error: Found non-normalized type (int[] & array<int>) for parameter $d: array<int> is a subtype of int[].
     * @param array<mixed&int> $f                 // error: Found non-normalized type (mixed & int) for parameter $f: int is a subtype of mixed.
     * @param list<mixed&int> $g                  // error: Found non-normalized type (mixed & int) for parameter $g: int is a subtype of mixed.
     * @param list<int>&array<int> $h             // error: Found non-normalized type (list<int> & array<int>) for parameter $h: list<int> is a subtype of array<int>.
     * @param ChildOne&MyInterface $i
     * @param InterfaceImplementor&MyInterface $j // error: Found non-normalized type (InterfaceImplementor & MyInterface) for parameter $j: InterfaceImplementor is a subtype of MyInterface.
     */
    public function testPhpDocIntersections($a, $b, $c, $d, $f, $g, $h, $i, $j): void
    {
    }

    public function testNativeUnions(
        ChildOne|BaseClass $i, // error: Found non-normalized type \ForbidNotNormalizedTypeRule\ChildOne|\ForbidNotNormalizedTypeRule\BaseClass for parameter $i: \ForbidNotNormalizedTypeRule\ChildOne is a subtype of \ForbidNotNormalizedTypeRule\BaseClass.
        ChildOne|MyInterface $j,
        InterfaceImplementor|MyInterface $k, // error: Found non-normalized type \ForbidNotNormalizedTypeRule\InterfaceImplementor|\ForbidNotNormalizedTypeRule\MyInterface for parameter $k: \ForbidNotNormalizedTypeRule\InterfaceImplementor is a subtype of \ForbidNotNormalizedTypeRule\MyInterface.


        // following are fatal errors, some reported even by native phpstan

        mixed|MyInterface $a,   // error: Found non-normalized type mixed|\ForbidNotNormalizedTypeRule\MyInterface for parameter $a: \ForbidNotNormalizedTypeRule\MyInterface is a subtype of mixed.
        ?mixed $b, // error: Found non-normalized type mixed|null for parameter $b: null is a subtype of mixed.
        null|mixed $c, // error: Found non-normalized type null|mixed for parameter $c: null is a subtype of mixed.
        true|bool $d, // error: Found non-normalized type true|bool for parameter $d: true is a subtype of bool.
        true|false $e,
    ): void
    {
    }

}