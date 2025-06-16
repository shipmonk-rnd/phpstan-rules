<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use function get_class;
use function sprintf;

/**
 * @implements Rule<Node>
 */
class ForbidFetchOnMixedRule implements Rule
{

    private Printer $printer;

    private bool $checkExplicitMixed;

    public function __construct(
        Printer $printer,
        bool $checkExplicitMixed
    )
    {
        $this->printer = $printer;
        $this->checkExplicitMixed = $checkExplicitMixed;
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
        Scope $scope
    ): array
    {
        if ($this->checkExplicitMixed) {
            return []; // already checked by native PHPStan
        }

        if ($node instanceof PropertyFetch || $node instanceof StaticPropertyFetch || $node instanceof ClassConstFetch) {
            return $this->processFetch($node, $scope);
        }

        return [];
    }

    /**
     * @param PropertyFetch|StaticPropertyFetch|ClassConstFetch $node
     * @return list<IdentifierRuleError>
     */
    private function processFetch(
        Node $node,
        Scope $scope
    ): array
    {
        $caller = $node instanceof PropertyFetch
            ? $node->var
            : $node->class;

        if (!$caller instanceof Expr) {
            return [];
        }

        $callerType = TypeUtils::toBenevolentUnion($scope->getType($caller));

        if (
            $callerType->getObjectTypeOrClassStringObjectType()->getObjectClassNames() === []
            && !$this->isObjectClassFetch($callerType, $node)
        ) {
            $name = $node->name;
            $propertyOrConstant = $name instanceof Identifier
                ? $this->printer->prettyPrint([$name])
                : $this->printer->prettyPrintExpr($name);
            $element = $node instanceof ClassConstFetch
                ? 'Constant'
                : 'Property';

            $errorMessage = sprintf(
                '%s fetch %s%s is prohibited on unknown type (%s)',
                $element,
                $this->getFetchToken($node),
                $propertyOrConstant,
                $this->printer->prettyPrintExpr($caller),
            );
            $error = RuleErrorBuilder::message($errorMessage)
                ->identifier('shipmonk.propertyFetchOnMixed')
                ->build();
            return [$error];
        }

        return [];
    }

    /**
     * @param PropertyFetch|StaticPropertyFetch|ClassConstFetch $node
     */
    private function getFetchToken(Node $node): string
    {
        switch (get_class($node)) {
            case ClassConstFetch::class:
            case StaticPropertyFetch::class:
                return '::';

            case PropertyFetch::class:
                return '->';

            default:
                throw new LogicException('Unexpected node given: ' . get_class($node));
        }
    }

    /**
     * Detect object::class
     *
     * @param PropertyFetch|StaticPropertyFetch|ClassConstFetch $node
     */
    private function isObjectClassFetch(
        Type $callerType,
        Node $node
    ): bool
    {
        $isObjectWithoutClassName = $callerType->isObject()->yes() && $callerType->getObjectClassNames() === [];

        if (!$isObjectWithoutClassName) {
            return false;
        }

        if (!$node instanceof ClassConstFetch) {
            return false;
        }

        if (!$node->name instanceof Identifier) {
            return false;
        }

        return $node->name->name === 'class';
    }

}
