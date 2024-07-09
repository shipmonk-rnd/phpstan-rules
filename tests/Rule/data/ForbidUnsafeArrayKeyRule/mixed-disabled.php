<?php declare(strict_types = 1);

namespace ForbidUnsafeArrayKeyRule\MixedDisabled;


class ArrayKey
{

    /**
     * @param array-key $arrayKey
     */
    public function test(
        int $int,
        string $string,
        float $float,
        $arrayKey,
        int|string $intOrString,
        int|float $intOrFloat,
        array $array,
        object $object,
        \WeakMap $weakMap,
        mixed $explicitMixed,
        $implicitMixed
    ) {
        $array[$array] = ''; // error: Array key must be integer or string, but array given.
        $array[$int] = '';
        $array[$string] = '';
        $array[$float] = ''; // error: Array key must be integer or string, but float given.
        $array[$intOrFloat] = ''; // error: Array key must be integer or string, but float|int given.
        $array[$intOrString] = '';
        $array[$explicitMixed] = '';
        $array[$implicitMixed] = '';
        $array[$arrayKey] = '';
        $weakMap[$object] = '';
    }
}


