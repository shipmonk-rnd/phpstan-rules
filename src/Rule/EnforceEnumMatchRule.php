<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Stmt\ElseIf_;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use function array_unique;
use function count;

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
        if ($elseif->cond->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME) === true) { // @phpstan-ignore-line ignore BC promise
            if (!$this->comparesEnums($elseif->cond, $scope)) {
                return [];
            }

            $conditionType = $scope->getType($elseif->cond);

            if ($conditionType->isTrue()->yes()) {
                return ['This else-if chain looks like exhaustive enum check. Rewrite it to match construct to ensure that error is raised when new enum case is added.'];
            }
        }

        return [];
    }

    private function comparesEnums(Expr $condition, Scope $scope): bool
    {
        if (!$condition instanceof Identical && !$condition instanceof NotIdentical) {
            return false;
        }

        $leftType = $scope->getType($condition->left);
        $rightType = $scope->getType($condition->right);

        if (!$leftType->isEnum()->yes() && !$rightType->isEnum()->yes()) {
            return false;
        }

        $enumClassNames = [];

        foreach ($leftType->getEnumCases() as $enumCase) {
            $enumClassNames[] = $enumCase->getClassName();
        }

        foreach ($rightType->getEnumCases() as $enumCase) {
            $enumClassNames[] = $enumCase->getClassName();
        }

        return count(array_unique($enumClassNames)) === 1;
    }

}
