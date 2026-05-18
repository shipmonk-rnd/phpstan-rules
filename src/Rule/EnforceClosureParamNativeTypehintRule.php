<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InArrowFunctionNode;
use PHPStan\Node\InClosureNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use function is_string;

/**
 * @implements Rule<Node>
 */
class EnforceClosureParamNativeTypehintRule implements Rule
{

    public function __construct(
        private readonly PhpVersion $phpVersion,
        private readonly bool $allowMissingTypeWhenInferred,
    )
    {
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
        Scope $scope,
    ): array
    {
        if (!$node instanceof InClosureNode && !$node instanceof InArrowFunctionNode) {
            return [];
        }

        if ($this->phpVersion->getVersionId() < 80_000) {
            return []; // unable to add mixed native typehint there
        }

        $errors = [];
        $type = $node instanceof InClosureNode ? 'closure' : 'arrow function';

        foreach ($node->getOriginalNode()->getParams() as $param) {
            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            if ($param->type !== null) {
                continue;
            }

            $paramType = $scope->getType($param->var);

            if ($this->allowMissingTypeWhenInferred && (!$paramType instanceof MixedType || $paramType->isExplicitMixed())) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message("Missing parameter typehint for {$type} parameter \${$param->var->name}.")
                ->identifier('shipmonk.unknownClosureParamType')
                ->line($param->getStartLine())
                ->build();
        }

        return $errors;
    }

}
