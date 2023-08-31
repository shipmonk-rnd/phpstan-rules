<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Type\MixedType;
use function get_class;
use function sprintf;

/**
 * @implements Rule<Node>
 */
class ForbidFetchOnMixedRule implements Rule
{

    private Standard $printer;

    private bool $checkExplicitMixed;

    public function __construct(Standard $printer, bool $checkExplicitMixed)
    {
        $this->printer = $printer;
        $this->checkExplicitMixed = $checkExplicitMixed;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<string> errors
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->checkExplicitMixed) {
            return []; // already checked by native PHPStan
        }

        if ($node instanceof PropertyFetch || $node instanceof StaticPropertyFetch) {
            return $this->processFetch($node, $scope);
        }

        return [];
    }

    /**
     * @param PropertyFetch|StaticPropertyFetch $node
     * @return list<string>
     */
    private function processFetch(Node $node, Scope $scope): array
    {
        $caller = $node instanceof PropertyFetch
            ? $node->var
            : $node->class;

        if (!$caller instanceof Expr) {
            return [];
        }

        $callerType = $scope->getType($caller);

        if ($callerType instanceof MixedType) {
            $name = $node->name;
            $property = $name instanceof Identifier
                ? $this->printer->prettyPrint([$name])
                : $this->printer->prettyPrintExpr($name);

            return [
                sprintf(
                    'Property fetch %s%s is prohibited on unknown type (%s)',
                    $this->getFetchToken($node),
                    $property,
                    $this->printer->prettyPrintExpr($caller),
                ),
            ];
        }

        return [];
    }

    /**
     * @param PropertyFetch|StaticPropertyFetch $node
     */
    private function getFetchToken(Node $node): string
    {
        switch (get_class($node)) {
            case StaticPropertyFetch::class:
                return '::';

            case PropertyFetch::class:
                return '->';

            default:
                throw new LogicException('Unexpected node given: ' . get_class($node));
        }
    }

}
