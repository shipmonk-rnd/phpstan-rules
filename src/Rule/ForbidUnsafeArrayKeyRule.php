<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr\ArrayDimFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<Node>
 */
class ForbidUnsafeArrayKeyRule implements Rule
{

    private bool $reportMixed;

    private bool $reportInsideIsset;

    public function __construct(
        bool $reportMixed,
        bool $reportInsideIsset
    )
    {
        $this->reportMixed = $reportMixed;
        $this->reportInsideIsset = $reportInsideIsset;
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
        if ($node instanceof ArrayItem && $node->key !== null) {
            $keyType = $scope->getType($node->key);

            if (!$this->isArrayKey($keyType)) {
                return [
                    RuleErrorBuilder::message('Array key must be integer or string, but ' . $keyType->describe(VerbosityLevel::precise()) . ' given.')
                        ->identifier('shipmonk.unsafeArrayKey')
                        ->build(),
                ];
            }
        }

        if (
            $node instanceof ArrayDimFetch
            && $node->dim !== null
            && !$scope->getType($node->var)->isArray()->no()
        ) {
            if (!$this->reportInsideIsset && $scope->isUndefinedExpressionAllowed($node)) {
                return [];
            }

            $dimType = $scope->getType($node->dim);

            if (!$this->isArrayKey($dimType)) {
                return [
                    RuleErrorBuilder::message('Array key must be integer or string, but ' . $dimType->describe(VerbosityLevel::precise()) . ' given.')
                        ->identifier('shipmonk.unsafeArrayKey')
                        ->build(),
                ];
            }
        }

        return [];
    }

    private function isArrayKey(Type $type): bool
    {
        if (!$this->reportMixed && $type instanceof MixedType) {
            return true;
        }

        return $this->containsOnlyTypes($type, [new StringType(), new IntegerType()]);
    }

    /**
     * @param list<Type> $allowedTypes
     */
    private function containsOnlyTypes(
        Type $checkedType,
        array $allowedTypes
    ): bool
    {
        $allowedType = TypeCombinator::union(...$allowedTypes);
        return $allowedType->isSuperTypeOf($checkedType)->yes();
    }

}
