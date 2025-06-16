<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use BackedEnum;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
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
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        $classReflection = $node->getClassReflection();
        $backedEnumType = $classReflection->getBackedEnumType();

        if ($backedEnumType === null) {
            return [];
        }

        if (!$this->isGenericBackedEnum($classReflection)) {
            return [];
        }

        $expectedType = $backedEnumType->describe(VerbosityLevel::typeOnly());
        $expectedTag = BackedEnum::class . "<$expectedType>";

        foreach ($classReflection->getAncestors() as $interface) {
            if ($this->hasGenericsTag($interface, $expectedTag)) {
                return [];
            }
        }

        $error = RuleErrorBuilder::message("Class {$classReflection->getName()} extends generic BackedEnum, but does not specify its type. Use @implements $expectedTag")
            ->identifier('shipmonk.missingImplementsOnBackedEnum')
            ->build();
        return [$error];
    }

    private function hasGenericsTag(
        ClassReflection $classReflection,
        string $expectedTag
    ): bool
    {
        if ($classReflection->isBackedEnum()) {
            $tags = $classReflection->getImplementsTags();
        } elseif ($classReflection->isInterface()) {
            $tags = $classReflection->getExtendsTags();
        } else {
            $tags = [];
        }

        foreach ($tags as $tag) {
            $implementsTagType = $tag->getType();

            if ($implementsTagType->describe(VerbosityLevel::typeOnly()) === $expectedTag) {
                return true;
            }
        }

        return false;
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
