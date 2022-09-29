<?php

namespace ForbidVariableTypeOverwritingRule;

// TODO https://phpstan.org/writing-php-code/phpdoc-types

class SomeClass {

}

class ParentClass1 extends SomeClass {

}

class ParentClass2 extends SomeClass {

}

function testSubtractedTypeNotKept(SomeClass $someClass) {
    if (!$someClass instanceof ParentClass1) {
        $someClass = new ParentClass1();
    }
}

function testGeneralizationAndNarrowing(
    SomeClass $someClass,
    ParentClass1 $parentClass1,
    ParentClass2 $parentClass2,
) {
    $parentClass1 = new SomeClass();
    $someClass = new ParentClass2();
    $parentClass2 = new ParentClass1(); // error: Overwriting variable $parentClass2 while changing its type from ForbidVariableTypeOverwritingRule\ParentClass2 to ForbidVariableTypeOverwritingRule\ParentClass1
}

/**
 * @param positive-int $positiveInt
 * @param int-mask-of<1|2|4> $intMask
 * @param list<int> $intList
 * @param 'foo'|'bar' $stringUnion
 * @param non-empty-string $nonEmptyString
 * @param non-empty-array<mixed> $nonEmptyArray
 * @param numeric-string $numericString
 */
function test(
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
    int|string $union
): void {

    // generalization and narrowing
    $positiveInt = $int;
    $union = $int;
    $intMask = $int;
    $stringUnion = $string;
    $stringUnion = 'another';

    // ignored types
    $null = null;
    $null = 'string';
    $mixed = $string;

    // errors
    $intList = ['string']; // error: Overwriting variable $intList while changing its type from array<int, int> to array<int, string>
    $nonEmptyArray = $int; // error: Overwriting variable $nonEmptyArray while changing its type from array to int
    $foo = '';
    $foo = 1; // error: Overwriting variable $foo while changing its type from string to int

    $list = [new SomeClass()];
    $list = ['foo']; // error: Overwriting variable $list while changing its type from array<int, ForbidVariableTypeOverwritingRule\SomeClass> to array<int, string>

    $class = new SomeClass();
    $class = new \stdClass(); // error: Overwriting variable $class while changing its type from ForbidVariableTypeOverwritingRule\SomeClass to stdClass

    $array = [];
    $array = 'string'; // error: Overwriting variable $array while changing its type from array<*NEVER*, *NEVER*> to string
}
