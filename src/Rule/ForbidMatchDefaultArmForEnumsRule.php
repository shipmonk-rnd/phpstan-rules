<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Match_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\NeverType;
use PHPStan\Type\TypeCombinator;
use function count;

/**
 * @implements Rule<Match_>
 */
class ForbidMatchDefaultArmForEnumsRule implements Rule
{

    public function getNodeType(): string
    {
        return Match_::class;
    }

    /**
     * @param Match_ $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope,
    ): array
    {
        $matchArgument = $scope->getType($node->cond);

        if (!$matchArgument->isEnum()->yes()) {
            return [];
        }

        foreach ($node->arms as $arm) {
            if ($arm->conds === null) {
                $error = RuleErrorBuilder::message('Default arm is denied for enums in match, list all values so that this case is raised when new enum case is added.')
                    ->line($arm->getStartLine())
                    ->identifier('shipmonk.defaultMatchArmWithEnum');

                if ($this->otherArmsCoverAllCases($node, $scope)) {
                    $error->removeNode($arm);
                }

                return [$error->build()];
            }
        }

        return [];
    }

    /**
     * Uses the native type, so that removing the arm cannot cause UnhandledMatchError for a value that PHPDoc excludes.
     */
    private function otherArmsCoverAllCases(
        Match_ $match,
        Scope $scope,
    ): bool
    {
        $remainingType = $scope->getNativeType($match->cond);

        if (!$remainingType->isEnum()->yes()) {
            return false;
        }

        foreach ($match->arms as $arm) {
            foreach ($arm->conds ?? [] as $condition) {
                $conditionType = $scope->getNativeType($condition);

                if ($conditionType->isEnum()->yes() && count($conditionType->getEnumCases()) === 1) {
                    $remainingType = TypeCombinator::remove($remainingType, $conditionType);
                }
            }
        }

        return $remainingType instanceof NeverType;
    }

}
