<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Helper;

use PHPStan\Type\IntersectionType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class EnumTypeHelper
{

    public static function containsEnum(Type $type): bool
    {
        foreach ($type->getArrays() as $arrayType) {
            if (self::containsEnum($arrayType->getItemType())) {
                return true;
            }
        }

        if ($type instanceof UnionType || $type instanceof IntersectionType) {
            foreach ($type->getTypes() as $innerType) {
                if (self::containsEnum($innerType)) {
                    return true;
                }
            }

            return false;
        }

        return self::isEnum($type);
    }

    public static function isEnum(Type $type): bool
    {
        return $type->getEnumCases() !== []; // TODO not 100% correct for enum Foo {}
    }

}
