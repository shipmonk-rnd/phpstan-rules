<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Node\Property\PropertyWrite;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeCombinator;
use ShipMonk\PHPStan\Visitor\ClassPropertyAssignmentVisitor;

/**
 * @implements Rule<ClassPropertiesNode>
 */
class UselessPrivatePropertyNullabilityRule implements Rule
{

    public function getNodeType(): string
    {
        return ClassPropertiesNode::class;
    }

    /**
     * @param ClassPropertiesNode $node
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return [];
        }

        $className = $classReflection->getName();

        $nullabilityNeeded = [];

        foreach ($node->getPropertyUsages() as $propertyUsage) {
            if (!$propertyUsage instanceof PropertyWrite) {
                continue;
            }

            $fetch = $propertyUsage->getFetch();

            if ($fetch->name instanceof Expr) {
                continue;
            }

            $propertyName = $fetch->name->toString();

            /** @var Expr|null $assignedExpr */
            $assignedExpr = $fetch->getAttribute(ClassPropertyAssignmentVisitor::ASSIGNED_EXPR);

            if ($assignedExpr === null) { // cases like object->array[] = value etc
                continue;
            }

            $assignedType = $propertyUsage->getScope()->getType($assignedExpr);

            if (TypeCombinator::containsNull($assignedType)) {
                $nullabilityNeeded[$propertyName] = true;
            }
        }

        [$uninitializedProperties] = $node->getUninitializedProperties($scope, $this->getConstructors($classReflection));

        $errors = [];

        foreach ($node->getProperties() as $property) {
            $shouldBeChecked = ($property->isPrivate() || $property->isReadOnly()) && !$property->isPromoted();

            if (!$shouldBeChecked) {
                continue;
            }

            $propertyName = $property->getName();
            $defaultValueNode = $property->getDefault();
            $propertyReflection = $classReflection->getProperty($propertyName, $scope);
            $definitionHasTypehint = $property->getNativeType() !== null;
            $definitionIsNullable = TypeCombinator::containsNull($propertyReflection->getWritableType());
            $nullIsAssigned = $nullabilityNeeded[$propertyName] ?? false;
            $hasNullDefaultValue = $defaultValueNode instanceof ConstFetch && $scope->resolveName($defaultValueNode->name) === 'null';
            $isUninitialized = isset($uninitializedProperties[$propertyName]);

            if ($definitionHasTypehint && $definitionIsNullable && !$nullIsAssigned && !$hasNullDefaultValue && !$isUninitialized) {
                $errors[] = RuleErrorBuilder::message("Property {$className}::{$propertyName} is defined as nullable, but null is never assigned")->line($property->getLine())->build();
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function getConstructors(ClassReflection $classReflection): array
    {
        $constructors = [];

        if ($classReflection->hasConstructor()) {
            $constructors[] = $classReflection->getConstructor()->getName();
        }

        return $constructors;
    }

}
