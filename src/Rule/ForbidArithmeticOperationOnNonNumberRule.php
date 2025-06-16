<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Div;
use PhpParser\Node\Expr\BinaryOp\Minus;
use PhpParser\Node\Expr\BinaryOp\Mod;
use PhpParser\Node\Expr\BinaryOp\Mul;
use PhpParser\Node\Expr\BinaryOp\Plus;
use PhpParser\Node\Expr\BinaryOp\Pow;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\UnaryPlus;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @implements Rule<Node>
 */
class ForbidArithmeticOperationOnNonNumberRule implements Rule
{

    private bool $allowNumericString;

    public function __construct(bool $allowNumericString)
    {
        $this->allowNumericString = $allowNumericString;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        if (
            $node instanceof UnaryPlus
            || $node instanceof UnaryMinus
        ) {
            return $this->processUnary($node->expr, $scope, $node instanceof UnaryMinus ? '-' : '+');
        }

        if (
            $node instanceof Plus
            || $node instanceof Minus
            || $node instanceof Div
            || $node instanceof Mul
            || $node instanceof Mod
            || $node instanceof Pow
        ) {
            return $this->processBinary($node->left, $node->right, $scope, $node->getOperatorSigil());
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processUnary(
        Expr $expr,
        Scope $scope,
        string $operator
    ): array
    {
        $exprType = $scope->getType($expr);

        if (!$this->isNumeric($exprType)) {
            $errorMessage = sprintf(
                'Using %s over non-number (%s)',
                $operator,
                $exprType->describe(VerbosityLevel::typeOnly()),
            );
            $error = RuleErrorBuilder::message($errorMessage)
                ->identifier('shipmonk.arithmeticOnNonNumber')
                ->build();
            return [$error];
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function processBinary(
        Expr $left,
        Expr $right,
        Scope $scope,
        string $operator
    ): array
    {
        $leftType = $scope->getType($left);
        $rightType = $scope->getType($right);

        if ($operator === '+' && $leftType->isArray()->yes() && $rightType->isArray()->yes()) {
            return []; // array merge syntax
        }

        if (
            $operator === '%' &&
            (!$leftType->isInteger()->yes() || !$rightType->isInteger()->yes())
        ) {
            return $this->buildBinaryErrors($operator, 'non-integer', $leftType, $rightType);
        }

        if (!$this->isNumeric($leftType) || !$this->isNumeric($rightType)) {
            return $this->buildBinaryErrors($operator, 'non-number', $leftType, $rightType);
        }

        return [];
    }

    private function isNumeric(Type $type): bool
    {
        $int = new IntegerType();
        $float = new FloatType();
        $intOrFloat = new UnionType([$int, $float]);

        return $int->isSuperTypeOf($type)->yes()
            || $float->isSuperTypeOf($type)->yes()
            || $intOrFloat->isSuperTypeOf($type)->yes()
            || ($this->allowNumericString && $type->isNumericString()->yes());
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function buildBinaryErrors(
        string $operator,
        string $type,
        Type $leftType,
        Type $rightType
    ): array
    {
        $errorMessage = sprintf(
            'Using %s over %s (%s %s %s)',
            $operator,
            $type,
            $leftType->describe(VerbosityLevel::typeOnly()),
            $operator,
            $rightType->describe(VerbosityLevel::typeOnly()),
        );
        $error = RuleErrorBuilder::message($errorMessage)
            ->identifier('shipmonk.arithmeticOnNonNumber')
            ->build();

        return [$error];
    }

}
