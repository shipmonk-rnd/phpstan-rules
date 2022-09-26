<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Rules\Rule;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<Identical>
 */
class ForbidUselessUnionCasesInConditionsRule implements Rule
{

    private Standard $printer;

    public function __construct(Standard $printer)
    {
        $this->printer = $printer;
    }

    public function getNodeType(): string
    {
        return Identical::class;
    }

    /**
     * @param Identical $node
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$scope instanceof MutatingScope) { // @phpstan-ignore-line ignore bc promise
            return [];
        }

        $leftType = $scope->getType($node->left);
        $rightType = $scope->getType($node->right);

        if ($leftType instanceof UnionType) {
            return $this->checkUnion($node->left, $leftType, $scope, $node->right);
        }

        if ($rightType instanceof UnionType) {
            return $this->checkUnion($node->right, $rightType, $scope, $node->left);
        }

        return [];
    }

    /**
     * @return string[]
     */
    private function checkUnion(Expr $expr, UnionType $exprType, MutatingScope $scope, Expr $other): array
    {
        $unionTypeString = $exprType->describe(VerbosityLevel::typeOnly());

        foreach ($exprType->getTypes() as $type) {
            $typeType = $type->describe(VerbosityLevel::precise());
            $exprString = $this->printer->prettyPrintExpr($expr);
            $sureTypes = [];
            $sureTypes[$exprString] = [$expr, $type];

            $specifiedTypes = new SpecifiedTypes($sureTypes);
            $alternativeScope = $scope->filterBySpecifiedTypes($specifiedTypes); // @phpstan-ignore-line ignore bc promise

            $alternativeCondition = new Identical($expr, $other);
            $alternativeConditionString = $this->printer->prettyPrintExpr($alternativeCondition);
            $alternativeType = $alternativeScope->getType($alternativeCondition);
            $alternativeTypeString = $alternativeType->describe(VerbosityLevel::value());

            if ($alternativeType instanceof ConstantBooleanType) {
                return ["{$alternativeConditionString} is always {$alternativeTypeString} when {$unionTypeString} is {$typeType}"];
            }
        }

        return [];
    }

}
