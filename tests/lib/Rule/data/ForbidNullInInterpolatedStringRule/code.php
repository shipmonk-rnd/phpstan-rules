<?php

namespace ForbidNullInInterpolatedStringRule;

class Foo {
    public ?int $nullableInt;
    public self $self;
}

/**
 * @param array{ id: ?int, strict: string } $array
 */
function test(
    bool $bool,
    ?bool $nullableBool,
    string $string,
    ?string $nullableString,
    Foo $foo,
    array $array
)
{
    echo "This is {$bool} and $nullableBool"; // error: Null value involved in string interpolation with $nullableBool
    echo "This is {$string}";
    echo "This is $nullableString"; // error: Null value involved in string interpolation with $nullableString
    echo "This is $foo->nullableInt"; // error: Null value involved in string interpolation with $foo->nullableInt
    echo "This is {$foo->self->self}";
    echo "This is $array[id]"; // error: Null value involved in string interpolation with $array['id']
    echo "This is $array[strict]";
    echo "This is {$array['id']}"; // error: Null value involved in string interpolation with $array['id']
}
