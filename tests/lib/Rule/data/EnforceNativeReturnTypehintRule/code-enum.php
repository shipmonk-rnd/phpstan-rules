<?php declare(strict_types = 1);

namespace EnforceNativeReturnTypehintRule;

enum MyBoolean: string
{
    case True = 'true';
    case False = 'false';

    public static function createFromBool(bool $boolValue) // error: Missing native return typehint self
    {
        return $boolValue ? self::True : self::False;
    }
}
