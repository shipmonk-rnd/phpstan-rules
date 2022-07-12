<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use DateTimeInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BinaryOp\Spaceship;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<BinaryOp>
 */
class ForbidTrickyComparisonRule implements Rule
{

    public function getNodeType(): string
    {
        return BinaryOp::class;
    }

    /**
     * @param BinaryOp $node
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (
            !$node instanceof Greater
            && !$node instanceof GreaterOrEqual
            && !$node instanceof Smaller
            && !$node instanceof SmallerOrEqual
            && !$node instanceof Spaceship
        ) {
            return [];
        }

        $leftType = $scope->getType($node->left);
        $rightType = $scope->getType($node->right);

        $leftTypeDescribed = $leftType->describe(VerbosityLevel::typeOnly());
        $rightTypeDescribed = $rightType->describe(VerbosityLevel::typeOnly());

        if (!$this->isComparable($leftType) || !$this->isComparable($rightType)) {
            return ["Comparison {$leftTypeDescribed} {$node->getOperatorSigil()} {$rightTypeDescribed} contains non-comparable type, only int|float|string|DateTimeInterface is allowed."];
        }

        return [];
    }

    private function isComparable(Type $type): bool
    {
        $intType = new IntegerType();
        $floatType = new FloatType();
        $stringType = new StringType();
        $dateTimeType = new ObjectType(DateTimeInterface::class);

        return $intType->isSuperTypeOf($type)->yes()
            || $floatType->isSuperTypeOf($type)->yes()
            || $stringType->isSuperTypeOf($type)->yes()
            || $dateTimeType->isSuperTypeOf($type)->yes();
    }

}
