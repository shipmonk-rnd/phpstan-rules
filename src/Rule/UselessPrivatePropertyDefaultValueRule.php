<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Node\Property\PropertyWrite;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use ShipMonk\PHPStan\Visitor\TopLevelConstructorPropertyFetchMarkingVisitor;

/**
 * @implements Rule<ClassPropertiesNode>
 */
class UselessPrivatePropertyDefaultValueRule implements Rule
{

    public function getNodeType(): string
    {
        return ClassPropertiesNode::class;
    }

    /**
     * @param ClassPropertiesNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope,
    ): array
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return [];
        }

        $className = $classReflection->getName();

        $noDefaultValueNeededProperties = [];

        foreach ($node->getPropertyUsages() as $propertyUsage) {
            if (!$propertyUsage instanceof PropertyWrite) {
                continue;
            }

            $fetch = $propertyUsage->getFetch();

            if ($fetch->name instanceof Expr) {
                continue;
            }

            $propertyName = $fetch->name->toString();

            // any assignment in top-level statement of constructor is considered to be always executed (guarded by ForbidReturnInConstructorRule)
            if ($fetch->getAttribute(TopLevelConstructorPropertyFetchMarkingVisitor::IS_TOP_LEVEL_CONSTRUCTOR_FETCH_ASSIGNMENT) === true) {
                $noDefaultValueNeededProperties[$propertyName] = true;
            }
        }

        $errors = [];

        foreach ($node->getProperties() as $property) {
            $propertyName = $property->getName();
            $shouldBeChecked = $property->isPrivate() && $property->getDefault() !== null;

            if (!$shouldBeChecked) {
                continue;
            }

            if (isset($noDefaultValueNeededProperties[$propertyName])) {
                $error = RuleErrorBuilder::message("Property {$className}::{$propertyName} has useless default value (overwritten in constructor)")
                    ->line($property->getStartLine())
                    ->identifier('shipmonk.uselessPrivatePropertyDefaultValue');

                $originalNode = $property->getOriginalNode();

                if ($originalNode instanceof Property) {
                    foreach ($originalNode->props as $propertyItem) {
                        if ($propertyItem->name->toString() === $propertyName) {
                            $error->fixNode($propertyItem, static function (PropertyItem $item): PropertyItem {
                                $item->default = null;
                                return $item;
                            });
                        }
                    }
                }

                $errors[] = $error->build();
            }
        }

        return $errors;
    }

}
