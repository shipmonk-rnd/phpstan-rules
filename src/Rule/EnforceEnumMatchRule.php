<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Stmt\ElseIf_;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<ElseIf_>
 */
class EnforceEnumMatchRule implements Rule
{

    public function getNodeType(): string
    {
        return ElseIf_::class;
    }

    /**
     * @param ElseIf_ $elseif
     * @return list<string>
     */
    public function processNode(Node $elseif, Scope $scope): array
    {
        if (
            $elseif->cond->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME) === true // @phpstan-ignore-line ignore BC promise
            && $elseif->cond instanceof Identical
            && $this->isAlwaysTrueEnumComparison($elseif->cond, $scope)
        ) {
            return ['This else-if chain looks like exhaustive enum check. Rewrite it to match construct to ensure that error is raised when new enum case is added.'];
        }

        return [];
    }

    private function isAlwaysTrueEnumComparison(Identical $condition, Scope $scope): bool
    {
        $conditionType = $scope->getType($condition);

        if (!$conditionType->isTrue()->yes()) {
            return false;
        }

        $leftType = $scope->getType($condition->left);
        $rightType = $scope->getType($condition->right);

        return $leftType->isEnum()->yes() && $rightType->isEnum()->yes();
    }

}
