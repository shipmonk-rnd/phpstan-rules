<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MatchExpressionNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;

/**
 * @implements Rule<MatchExpressionNode>
 */
class ForbidMatchDefaultArmForEnumsRule implements Rule
{

    public function getNodeType(): string
    {
        return MatchExpressionNode::class;
    }

    /**
     * @param MatchExpressionNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        $matchCondition = $node->getCondition();
        $matchArgument = $scope->getType($matchCondition);

        if (!$matchArgument->isEnum()->yes()) {
            return [];
        }

        foreach ($node->getArms() as $arm) {
            if (count($arm->getConditions()) === 0) {
                $error = RuleErrorBuilder::message('Default arm is denied for enums in match, list all values so that this case is raised when new enum case is added.')
                    ->line($arm->getLine())
                    ->identifier('shipmonk.defaultMatchArmWithEnum')
                    ->build();
                return [$error];
            }
        }

        return [];
    }

}
