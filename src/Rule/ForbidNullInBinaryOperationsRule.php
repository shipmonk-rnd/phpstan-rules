<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function in_array;

/**
 * @implements Rule<BinaryOp>
 */
class ForbidNullInBinaryOperationsRule implements Rule
{

    private const DEFAULT_BLACKLIST = ['===', '!==', '??'];

    /**
     * @var list<string>
     */
    private array $blacklist;

    /**
     * @param list<string> $blacklist
     */
    public function __construct(array $blacklist = self::DEFAULT_BLACKLIST)
    {
        $this->blacklist = $blacklist;
    }

    public function getNodeType(): string
    {
        return BinaryOp::class;
    }

    /**
     * @param BinaryOp $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        if (in_array($node->getOperatorSigil(), $this->blacklist, true)) {
            return [];
        }

        $leftType = $scope->getType($node->left);
        $rightType = $scope->getType($node->right);

        $leftTypeDescribed = $leftType->describe(VerbosityLevel::typeOnly());
        $rightTypeDescribed = $rightType->describe(VerbosityLevel::typeOnly());

        if (TypeCombinator::containsNull($leftType) || TypeCombinator::containsNull($rightType)) {
            $error = RuleErrorBuilder::message("Null value involved in binary operation: {$leftTypeDescribed} {$node->getOperatorSigil()} {$rightTypeDescribed}")
                ->identifier('shipmonk.binaryOperationWithNull')
                ->build();
            return [$error];
        }

        return [];
    }

}
