<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\SubtractableType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
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
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
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
            $error = RuleErrorBuilder::message("Overwriting variable \$$variableName while changing its type from {$previousVariableType->describe(VerbosityLevel::precise())} to {$newVariableType->describe(VerbosityLevel::precise())}")
                ->identifier('shipmonk.variableTypeOverwritten')
                ->build();
            return [$error];
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
            || $type instanceof IntegerRangeType
            || $type instanceof EnumCaseObjectType // @phpstan-ignore phpstanApi.instanceofType
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

        if ($type instanceof IntersectionType) { // @phpstan-ignore phpstanApi.instanceofType
            $newInnerTypes = [];

            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof AccessoryType) {
                    continue;
                }

                $newInnerTypes[] = $innerType;
            }

            $type = TypeCombinator::intersect(...$newInnerTypes);
        }

        if ($type instanceof SubtractableType) {
            $type = $type->getTypeWithoutSubtractedType();
        }

        return TypeCombinator::removeNull($type);
    }

}
