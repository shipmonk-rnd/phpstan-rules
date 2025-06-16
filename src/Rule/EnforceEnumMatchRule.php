<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Enum\EnumCaseObjectType;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function count;

/**
 * @implements Rule<BinaryOp>
 */
class EnforceEnumMatchRule implements Rule
{

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
        if (!$node instanceof Identical && !$node instanceof NotIdentical) {
            return [];
        }

        $conditionType = $scope->getType($node);

        if (!$conditionType->isTrue()->yes() && !$conditionType->isFalse()->yes()) {
            return [];
        }

        $leftType = $scope->getType($node->left);
        $rightType = $scope->getType($node->right);

        if ($leftType->isEnum()->yes() && $rightType->isEnum()->yes()) {
            $enumCases = array_values(array_unique(
                array_merge(
                    array_map(static fn (EnumCaseObjectType $type) => "{$type->getClassName()}::{$type->getEnumCaseName()}", $leftType->getEnumCases()),
                    array_map(static fn (EnumCaseObjectType $type) => "{$type->getClassName()}::{$type->getEnumCaseName()}", $rightType->getEnumCases()),
                ),
            ));

            if (count($enumCases) !== 1) {
                return []; // do not report nonsense comparison
            }

            $trueFalse = $conditionType->isTrue()->yes() ? 'true' : 'false';
            $error = RuleErrorBuilder::message("This condition contains always-$trueFalse enum comparison of $enumCases[0]. Use match expression instead, PHPStan will report unhandled enum cases")
                ->identifier('shipmonk.enumMatchNotUsed')
                ->build();
            return [$error];
        }

        return [];
    }

}
