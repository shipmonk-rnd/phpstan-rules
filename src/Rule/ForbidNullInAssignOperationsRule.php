<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr\AssignOp;
use PhpParser\Node\Expr\AssignOp\BitwiseAnd;
use PhpParser\Node\Expr\AssignOp\BitwiseOr;
use PhpParser\Node\Expr\AssignOp\BitwiseXor;
use PhpParser\Node\Expr\AssignOp\Coalesce;
use PhpParser\Node\Expr\AssignOp\Concat;
use PhpParser\Node\Expr\AssignOp\Div;
use PhpParser\Node\Expr\AssignOp\Minus;
use PhpParser\Node\Expr\AssignOp\Mod;
use PhpParser\Node\Expr\AssignOp\Mul;
use PhpParser\Node\Expr\AssignOp\Plus;
use PhpParser\Node\Expr\AssignOp\Pow;
use PhpParser\Node\Expr\AssignOp\ShiftLeft;
use PhpParser\Node\Expr\AssignOp\ShiftRight;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeCombinator;
use function get_class;
use function in_array;

/**
 * @implements Rule<AssignOp>
 */
class ForbidNullInAssignOperationsRule implements Rule
{

    private const DEFAULT_BLACKLIST = ['??='];

    /**
     * @var string[]
     */
    private array $blacklist;

    /**
     * @param string[] $blacklist
     */
    public function __construct(array $blacklist = self::DEFAULT_BLACKLIST)
    {
        $this->blacklist = $blacklist;
    }

    public function getNodeType(): string
    {
        return AssignOp::class;
    }

    /**
     * @param AssignOp $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        $exprType = $scope->getType($node->expr);
        $operator = $this->getOperatorString($node);

        if (TypeCombinator::containsNull($exprType) && !in_array($operator, $this->blacklist, true)) {
            $error = RuleErrorBuilder::message("Null value involved in {$operator} assignment on the right side.")
                ->identifier('shipmonk.assignmentWithNull')
                ->build();
            return [$error];
        }

        return [];
    }

    private function getOperatorString(AssignOp $node): string
    {
        if ($node instanceof Mul) {
            return '*=';
        }

        if ($node instanceof Coalesce) {
            return '??=';
        }

        if ($node instanceof ShiftRight) {
            return '>>=';
        }

        if ($node instanceof Minus) {
            return '-=';
        }

        if ($node instanceof Mod) {
            return '%=';
        }

        if ($node instanceof BitwiseXor) {
            return '^=';
        }

        if ($node instanceof Concat) {
            return '.=';
        }

        if ($node instanceof Div) {
            return '/=';
        }

        if ($node instanceof Plus) {
            return '+=';
        }

        if ($node instanceof ShiftLeft) {
            return '<<=';
        }

        if ($node instanceof BitwiseOr) {
            return '|=';
        }

        if ($node instanceof Pow) {
            return '**=';
        }

        if ($node instanceof BitwiseAnd) {
            return '&=';
        }

        throw new LogicException('Unexpected AssignOp child: ' . get_class($node));
    }

}
