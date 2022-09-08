<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use BackedEnum;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<InClassNode>
 */
class BackedEnumGenericsRule implements Rule
{

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     * @return RuleError[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();
        $backedEnumType = $classReflection->getBackedEnumType();

        if ($backedEnumType === null) {
            return [];
        }

        if (!$this->isGenericBackedEnum($classReflection)) {
            return [];
        }

        $declaredBackingType = $this->getDeclaredEnumBackingType($classReflection, $backedEnumType);

        if ($declaredBackingType === null) {
            $expectedTypeString = $backedEnumType->describe(VerbosityLevel::typeOnly());
            $expectedTag = BackedEnum::class . "<$expectedTypeString>";

            return [RuleErrorBuilder::message("Class {$classReflection->getName()} extends generic BackedEnum, but does not specify its type. Use @implements {$expectedTag}")->build()];
        }

        $errors = [];

        foreach ($classReflection->getEnumCases() as $enumCase) {
            if (
                $enumCase->getBackingValueType() !== null
                && !$declaredBackingType->accepts($enumCase->getBackingValueType(), $scope->isDeclareStrictTypes())->yes()
            ) {
                $enumValue = $enumCase->getBackingValueType()->describe(VerbosityLevel::precise());
                $declaredBackingTypeString = $declaredBackingType->describe(VerbosityLevel::precise());

                $errors[] = RuleErrorBuilder::message("Enum case {$enumCase->getName()} ({$enumValue}) does not match declared type {$declaredBackingTypeString}")->build();
                // TODO wrong line reported
            }
        }

        return $errors;
    }

    private function getDeclaredEnumBackingType(ClassReflection $classReflection, Type $enumBackingType): ?Type
    {
        foreach ($classReflection->getAncestors() as $ancestor) {
            if ($ancestor->isBackedEnum()) {
                $tags = $ancestor->getImplementsTags();
            } elseif ($ancestor->isInterface()) {
                $tags = $ancestor->getExtendsTags();
            } else {
                $tags = [];
            }

            foreach ($tags as $tag) {
                $implementsTagType = $tag->getType();

                if (
                    $implementsTagType instanceof GenericObjectType
                    && $implementsTagType->getClassReflection()?->getName() === BackedEnum::class
                ) {
                    foreach ($implementsTagType->getTypes() as $type) {
                        if ($enumBackingType->isSuperTypeOf($type)->yes()) {
                            return $type;
                        } // TODO else error
                    }
                }
            }
        }

        return null;
    }

    private function isGenericBackedEnum(ClassReflection $classReflection): bool
    {
        foreach ($classReflection->getAncestors() as $ancestor) {
            if ($ancestor->getName() === BackedEnum::class && $ancestor->isGeneric()) {
                return true;
            }
        }

        return false;
    }

}
