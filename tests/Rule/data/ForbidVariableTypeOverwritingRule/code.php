<?php

namespace ForbidVariableTypeOverwritingRule;

/**
 * Make PHPStan lose info about possible descendants (new Foo)
 *
 * @template T
 * @param T
 * @return T
 */
function passThru($iterable) {
    return $iterable;
}

interface SomeInterface {

}

class ParentClass {

}

class ChildClass1 extends ParentClass {

}

class ChildClass2 extends ParentClass {

}

class AnotherClassWithInterface implements SomeInterface {

}

enum FooEnum {
    case One;
    case Two;
}

function testGeneralizationAndNarrowing(
    object $object,
    SomeInterface $interface,
    SomeInterface&ParentClass $classWithInterface1,
    SomeInterface&ParentClass $classWithInterface2,
    SomeInterface&ParentClass $classWithInterface3,
    int|string $intOrString1,
    int|string $intOrString2,
    ParentClass $parentClass,
    ChildClass1 $childClass1,
    ChildClass2 $childClass2,
) {
    $childClass1 = passThru(new ParentClass());
    $parentClass = new ChildClass2();
    $childClass2 = new ChildClass1(); // error: Overwriting variable $childClass2 while changing its type from ForbidVariableTypeOverwritingRule\ChildClass2 to ForbidVariableTypeOverwritingRule\ChildClass1

    $object = new ParentClass();
    $intOrString1 = 1;
    $intOrString2 = []; // error: Overwriting variable $intOrString2 while changing its type from int|string to array{}
    $classWithInterface1 = passThru(new ParentClass());
    $classWithInterface2 = new AnotherClassWithInterface(); // error: Overwriting variable $classWithInterface2 while changing its type from ForbidVariableTypeOverwritingRule\ParentClass&ForbidVariableTypeOverwritingRule\SomeInterface to ForbidVariableTypeOverwritingRule\AnotherClassWithInterface
    $classWithInterface3 = $interface;
}

/**
 * @param array $array
 * @param list<int> $intList
 * @param list<ParentClass> $objectList
 * @param array<string, string> $map
 */
function testBasics(
    array $array,
    array $objectList,
    string $string,
    ParentClass $class,
    array $map,
    array $intList = [1],
): void {
    $intList = ['string']; // error: Overwriting variable $intList while changing its type from array<int, int> to array<int, string>
    $array = 1; // error: Overwriting variable $array while changing its type from array to int
    $string = 1; // error: Overwriting variable $string while changing its type from string to int
    $objectList = ['foo']; // error: Overwriting variable $objectList while changing its type from array<int, ForbidVariableTypeOverwritingRule\ParentClass> to array<int, string>
    $class = new \stdClass(); // error: Overwriting variable $class while changing its type from ForbidVariableTypeOverwritingRule\ParentClass to stdClass
    $map = [1]; // error: Overwriting variable $map while changing its type from array<string, string> to array<int, int>
}

function testIgnoredTypes(
    $mixed1,
    $mixed2,
    $mixed3,
    $mixed4,
    ?ParentClass $parentClass1,
    ParentClass $parentClass2,
): void {
    $null = null;
    $null = '';
    $mixed1 = '';
    $mixed2 = 1;
    $mixed3 = null;
    $parentClass1 = null;
    $parentClass2 = $mixed4;
}

/**
 * @param positive-int $positiveInt
 * @param int-mask-of<1|2|4> $intMask
 * @param list<int> $intList
 * @param 'foo'|'bar' $stringUnion
 * @param non-empty-string $nonEmptyString
 * @param non-empty-array<mixed> $nonEmptyArray
 * @param numeric-string $numericString
 * @param array<'key1'|'key2', class-string> $strictArray
 */
function testAdvancedTypesAreIgnored(
    array $nonEmptyArray,
    array $intList,
    $mixed,
    int $int,
    int $positiveInt,
    int $intMask,
    string $string,
    string $stringUnion,
    string $nonEmptyString,
    string $numericString,
    array $strictArray,
): void {
    $positiveInt = $int;
    $intMask = $int;
    $stringUnion = $string;
    $nonEmptyArray = ['string'];
    $intList = [1];
    $mixed = $nonEmptyArray['unknown'];
    $nonEmptyString = ' ';
    $numericString = 'not-a-number';
    $strictArray = ['string' => 'string'];
}

/**
 * @param ParentClass $someClass
 * @param mixed[] $strings
 */
function testSubtractedTypeNotKept(ParentClass $someClass, array $strings) {
    if (!$someClass instanceof ChildClass1) {
        $someClass = new ChildClass1();
    }

    unset($strings[0]);
    $strings = array_values($strings);
}

function testEnumCaseChange() {
    $case1 = FooEnum::One;
    $case1 = FooEnum::Two;
}

/**
 * @param positive-int $positive
 * @param negative-int $negative
 *
 * @return void
 */
function testIntToInt(int $positive, int $negative) {
    $positive = $negative;
}

function testFloatToInt(float $float, int $int) {
    $float = $int; // error: Overwriting variable $float while changing its type from float to int
}

function testIntToFloat(int $int, float $float) {
    $int = $float; // error: Overwriting variable $int while changing its type from int to float
}

class FluentClass {
    /** @return $this */
    public function orderBy(): static {
        return $this;
    }

    /** @return $this */
    public function setFirstResult(): static {
        return $this;
    }

    /** @return $this */
    public function setMaxResults(): static {
        return $this;
    }
}

function testFluentInterfaceSameType(FluentClass $qb): void {
    $qb = $qb
        ->orderBy()
        ->setFirstResult()
        ->setMaxResults();
}

abstract class AbstractBuilder {
    /** @return $this */
    public function orderBy(): self {
        return $this;
    }

    /** @return $this */
    public function setFirstResult(): self {
        return $this;
    }

    /** @return $this */
    public function setMaxResults(): self {
        return $this;
    }
}

class ConcreteBuilder extends AbstractBuilder {
}

function testFluentInterfaceInherited(ConcreteBuilder $qb): void {
    $qb = $qb
        ->orderBy()
        ->setFirstResult()
        ->setMaxResults();
}
