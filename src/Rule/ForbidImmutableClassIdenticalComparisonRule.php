<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use DateTimeImmutable;
use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\VerbosityLevel;
use function count;

/**
 * @implements Rule<BinaryOp>
 */
class ForbidImmutableClassIdenticalComparisonRule implements Rule
{

    private const DEFAULT_BLACKLIST = [DateTimeImmutable::class];

    /**
     * @var array<int, class-string<object>>
     */
    private array $blacklist;

    /**
     * @param array<int, class-string<object>> $blacklist
     */
    public function __construct(
        ReflectionProvider $reflectionProvider,
        array $blacklist = self::DEFAULT_BLACKLIST
    )
    {
        foreach ($blacklist as $className) {
            if (!$reflectionProvider->hasClass($className)) {
                throw new LogicException("Class {$className} does not exist.");
            }
        }

        $this->blacklist = $blacklist;
    }

    public function getNodeType(): string
    {
        return BinaryOp::class;
    }

    /**
     * @param BinaryOp $node
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (count($this->blacklist) === 0) {
            return [];
        }

        if (!$node instanceof Identical && !$node instanceof NotIdentical) {
            return [];
        }

        $nodeType = $scope->getType($node);
        $rightType = $scope->getType($node->right);
        $leftType = $scope->getType($node->left);

        if ($nodeType instanceof ConstantBooleanType) {
            return []; // always-true or always-false, already reported by native PHPStan (like $a === $a)
        }

        if (
            $leftType instanceof MixedType
            || $leftType instanceof ObjectWithoutClassType
            || $rightType instanceof MixedType
            || $rightType instanceof ObjectWithoutClassType
        ) {
            return []; // those may contain forbidden class, but that is too strict
        }

        $errors = [];

        foreach ($this->blacklist as $className) {
            $forbiddenObjectType = new ObjectType($className);

            if (
                !$forbiddenObjectType->accepts($leftType, $scope->isDeclareStrictTypes())->no()
                && !$forbiddenObjectType->accepts($rightType, $scope->isDeclareStrictTypes())->no()
            ) {
                $errors[] = "Using {$node->getOperatorSigil()} with {$forbiddenObjectType->describe(VerbosityLevel::typeOnly())} is denied";
            }
        }

        return $errors;
    }

}
