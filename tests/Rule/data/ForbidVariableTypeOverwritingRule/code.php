<?php

namespace ForbidVariableTypeOverwritingRule;

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
    $childClass1 = new ParentClass();
    $parentClass = new ChildClass2();
    $childClass2 = new ChildClass1(); // error: Overwriting variable $childClass2 while changing its type from ForbidVariableTypeOverwritingRule\ChildClass2 to ForbidVariableTypeOverwritingRule\ChildClass1

    $object = new ParentClass();
    $intOrString1 = 1;
    $intOrString2 = []; // error: Overwriting variable $intOrString2 while changing its type from int|string to array{}
    $classWithInterface1 = new ParentClass();
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
    $intList = ['string']; // error: Overwriting variable $intList while changing its type from array<int<0, max>, int> to array<int<0, max>, string>
    $array = 1; // error: Overwriting variable $array while changing its type from array to int
    $string = 1; // error: Overwriting variable $string while changing its type from string to int
    $objectList = ['foo']; // error: Overwriting variable $objectList while changing its type from array<int<0, max>, ForbidVariableTypeOverwritingRule\ParentClass> to array<int<0, max>, string>
    $class = new \stdClass(); // error: Overwriting variable $class while changing its type from ForbidVariableTypeOverwritingRule\ParentClass to stdClass
    $map = [1]; // error: Overwriting variable $map while changing its type from array<string, string> to array<int<0, max>, int>
}

function testIgnoredTypes(
    mixed $mixed1,
    mixed $mixed2,
    mixed $mixed3,
    mixed $mixed4,
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
    mixed $mixed,
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
