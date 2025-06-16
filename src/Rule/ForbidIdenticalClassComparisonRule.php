<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use DateTimeInterface;
use LogicException;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function count;

/**
 * @implements Rule<BinaryOp>
 */
class ForbidIdenticalClassComparisonRule implements Rule
{

    private const DEFAULT_BLACKLIST = [DateTimeInterface::class];

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
                throw new LogicException("Class {$className} used in 'forbidIdenticalClassComparison' does not exist.");
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
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
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

        if ($nodeType->isTrue()->yes() || $nodeType->isFalse()->yes()) {
            return []; // always-true or always-false, already reported by native PHPStan (like $a === $a)
        }

        $errors = [];

        foreach ($this->blacklist as $className) {
            $forbiddenObjectType = new ObjectType($className);

            if (
                $this->containsClass($leftType, $className)
                && $this->containsClass($rightType, $className)
            ) {
                $errors[] = RuleErrorBuilder::message("Using {$node->getOperatorSigil()} with {$forbiddenObjectType->describe(VerbosityLevel::typeOnly())} is denied")
                    ->identifier('shipmonk.deniedClassComparison')
                    ->build();

            }
        }

        return $errors;
    }

    private function containsClass(
        Type $type,
        string $className
    ): bool
    {
        $benevolentType = TypeUtils::toBenevolentUnion($type);

        foreach ($benevolentType->getObjectClassNames() as $classNameInType) {
            $classInType = new ObjectType($classNameInType);

            if ($classInType->isInstanceOf($className)->yes()) {
                return true;
            }
        }

        return false;
    }

}
