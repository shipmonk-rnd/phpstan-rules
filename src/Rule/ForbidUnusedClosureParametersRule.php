<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_map;
use function array_slice;
use function array_values;
use function count;
use function is_string;

/**
 * @implements Rule<Expr>
 */
final class ForbidUnusedClosureParametersRule implements Rule
{

    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @param Expr $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        if ($node instanceof ArrowFunction) {
            $referencedVariables = $this->getReferencedVariables([$node->expr]);

        } elseif ($node instanceof Closure) {
            $referencedVariables = $this->getReferencedVariables($node->stmts);

        } else {
            return [];
        }

        $errors = [];
        $trailingUnusedParameterNames = $this->getTrailingUnusedParameterNames(array_values($node->params), $referencedVariables);

        $functionType = $node instanceof ArrowFunction ? 'Arrow function' : 'Closure';
        foreach ($trailingUnusedParameterNames as $parameterName) {
            $errors[] = RuleErrorBuilder::message("{$functionType} parameter \${$parameterName} is unused")
                ->identifier('shipmonk.unusedParameter')
                ->build();
        }

        return $errors;
    }

    /**
     * @param array<Node> $nodes
     * @return array<string, true>
     */
    private function getReferencedVariables(array $nodes): array
    {
        $visitor = new class extends NodeVisitorAbstract {

            /**
             * @var array<string, true>
             */
            private array $usedVariables = [];

            public function enterNode(Node $node): ?Node
            {
                if ($node instanceof Variable && is_string($node->name)) {
                    $this->usedVariables[$node->name] = true;
                }

                return null;
            }

            /**
             * @return array<string, true>
             */
            public function getUsedVariables(): array
            {
                return $this->usedVariables;
            }

        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        return $visitor->getUsedVariables();
    }

    /**
     * @param list<Param> $params
     * @param array<string, true> $referencedVariables
     * @return list<string>
     */
    private function getTrailingUnusedParameterNames(
        array $params,
        array $referencedVariables
    ): array
    {
        for ($i = count($params) - 1; $i >= 0; $i--) {
            $param = $params[$i]; // @phpstan-ignore offsetAccess.notFound

            if (!$param->var instanceof Variable) {
                continue;
            }

            if (!is_string($param->var->name)) {
                continue;
            }

            $parameterName = $param->var->name;

            if (isset($referencedVariables[$parameterName])) {
                return $this->extractParamNames(array_slice($params, $i + 1));
            }
        }

        return $this->extractParamNames($params);
    }

    /**
     * @param array<Param> $params
     * @return list<string>
     */
    private function extractParamNames(array $params): array
    {
        return array_values(array_map(static function (Param $param): string {
            if (!$param->var instanceof Variable) {
                throw new LogicException('Param variable must be a variable');
            }
            if (!is_string($param->var->name)) {
                throw new LogicException('Param variable name must be a string');
            }
            return $param->var->name;
        }, $params));
    }

}
