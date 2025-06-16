<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassMethodsNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassMethodsNode>
 */
class ForbidProtectedEnumMethodRule implements Rule
{

    public function getNodeType(): string
    {
        return ClassMethodsNode::class;
    }

    /**
     * @param ClassMethodsNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        if ($scope->getClassReflection() === null || !$scope->getClassReflection()->isEnum()) {
            return [];
        }

        $errors = [];

        foreach ($node->getMethods() as $classMethod) {
            if (
                $classMethod->getNode()->isProtected()
                && !$classMethod->isDeclaredInTrait()
            ) {
                $errors[] = RuleErrorBuilder::message('Protected methods within enum makes no sense as you cannot extend them anyway.')
                    ->line($classMethod->getNode()->getStartLine())
                    ->identifier('shipmonk.protectedEnumMethod')
                    ->build();
            }
        }

        return $errors;
    }

}
