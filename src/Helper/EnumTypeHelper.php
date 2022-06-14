<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Helper;

use PHPStan\Type\ArrayType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;

class EnumTypeHelper
{

    public static function containsEnum(Type $type): bool
    {
        if ($type instanceof ArrayType && self::containsEnum($type->getItemType())) {
            return true;
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
        if (!$type instanceof TypeWithClassName) {
            return false;
        }

        $classReflection = $type->getClassReflection();

        if ($classReflection === null) {
            return false;
        }

        return $classReflection->isEnum();
    }

}
