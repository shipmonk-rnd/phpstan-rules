<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\SubtractableType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<Assign>
 */
class ForbidVariableTypeOverwritingRule implements Rule
{

    public function getNodeType(): string
    {
        return Assign::class;
    }

    /**
     * @param Assign $node
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->var instanceof Variable) {
            return []; // array append not yet supported
        }

        $variableName = $node->var->name;

        if ($variableName instanceof Expr) {
            return []; // no support for cases like $$foo
        }

        if (!$scope->hasVariableType($variableName)->yes()) {
            return [];
        }

        $previousVariableType = $this->generalizeDeeply($scope->getVariableType($variableName));
        $newVariableType = $this->generalizeDeeply($scope->getType($node->expr));

        if ($this->isTypeToIgnore($previousVariableType) || $this->isTypeToIgnore($newVariableType)) {
            return [];
        }

        if (
            !$previousVariableType->isSuperTypeOf($newVariableType)->yes() // allow narrowing
            && !$newVariableType->isSuperTypeOf($previousVariableType)->yes() // allow generalization
        ) {
            return ["Overwriting variable \$$variableName while changing its type from {$previousVariableType->describe(VerbosityLevel::precise())} to {$newVariableType->describe(VerbosityLevel::precise())}"];
        }

        return [];
    }

    private function generalizeDeeply(Type $type): Type
    {
        return TypeTraverser::map($type, function (Type $traversedTyped, callable $traverse): Type {
            return $traverse($this->generalize($traversedTyped));
        });
    }

    private function generalize(Type $type): Type
    {
        if (
            $type->isConstantValue()->yes()
            || $type->getEnumCases() !== []
            || $type instanceof IntegerRangeType
            || $type instanceof UnionType // e.g. 'foo'|'bar' -> string or int<min, -1>|int<1, max> -> int
        ) {
            $type = $type->generalize(GeneralizePrecision::lessSpecific());
        }

        if ($type->isNull()->yes()) {
            return $type;
        }

        return $this->removeNullAccessoryAndSubtractedTypes($type);
    }

    private function isTypeToIgnore(Type $type): bool
    {
        return $type->isNull()->yes() || $type instanceof MixedType;
    }

    private function removeNullAccessoryAndSubtractedTypes(Type $type): Type
    {
        if ($type->isNull()->yes()) {
            return $type;
        }

        if ($type instanceof IntersectionType) {
            $newInnerTypes = [];

            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof AccessoryType) { // @phpstan-ignore-line ignore bc promise
                    continue;
                }

                $newInnerTypes[] = $innerType;
            }

            $type = TypeCombinator::intersect(...$newInnerTypes);
        }

        if ($type instanceof SubtractableType) { // @phpstan-ignore-line ignore bc promise
            $type = $type->getTypeWithoutSubtractedType(); // @phpstan-ignore-line ignore bc promise
        }

        return TypeCombinator::removeNull($type);
    }

}
