<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use DateTimeInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp;
use PhpParser\Node\Expr\BinaryOp\Greater;
use PhpParser\Node\Expr\BinaryOp\GreaterOrEqual;
use PhpParser\Node\Expr\BinaryOp\Smaller;
use PhpParser\Node\Expr\BinaryOp\SmallerOrEqual;
use PhpParser\Node\Expr\BinaryOp\Spaceship;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<BinaryOp>
 */
class AllowComparingOnlyComparableTypesRule implements Rule
{

    public function getNodeType(): string
    {
        return BinaryOp::class;
    }

    /**
     * @param BinaryOp $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (
            !$node instanceof Greater
            && !$node instanceof GreaterOrEqual
            && !$node instanceof Smaller
            && !$node instanceof SmallerOrEqual
            && !$node instanceof Spaceship
        ) {
            return [];
        }

        $leftType = $scope->getType($node->left);
        $rightType = $scope->getType($node->right);

        $leftTypeDescribed = $leftType->describe(VerbosityLevel::typeOnly());
        $rightTypeDescribed = $rightType->describe(VerbosityLevel::typeOnly());

        if (!$this->isComparable($leftType) || !$this->isComparable($rightType)) {
            $error = RuleErrorBuilder::message("Comparison {$leftTypeDescribed} {$node->getOperatorSigil()} {$rightTypeDescribed} contains non-comparable type, only int|float|string|DateTimeInterface is allowed.")
                ->identifier('comparingNonComparableTypes')
                ->build();
            return [$error];
        }

        if (!$this->isComparableTogether($leftType, $rightType)) {
            $error = RuleErrorBuilder::message("Cannot compare different types in {$leftTypeDescribed} {$node->getOperatorSigil()} {$rightTypeDescribed}.")
                ->identifier('comparingNonComparableTypes')
                ->build();
            return [$error];
        }

        return [];
    }

    private function isComparable(Type $type): bool
    {
        $intType = new IntegerType();
        $floatType = new FloatType();
        $stringType = new StringType();
        $dateTimeType = new ObjectType(DateTimeInterface::class);

        return $this->containsOnlyTypes($type, [$intType, $floatType, $stringType, $dateTimeType]);
    }

    private function isComparableTogether(Type $leftType, Type $rightType): bool
    {
        $intType = new IntegerType();
        $floatType = new FloatType();
        $stringType = new StringType();
        $dateTimeType = new ObjectType(DateTimeInterface::class);

        return ($this->containsOnlyTypes($leftType, [$intType, $floatType]) && $this->containsOnlyTypes($rightType, [$intType, $floatType]))
            || ($this->containsOnlyTypes($leftType, [$stringType]) && $this->containsOnlyTypes($rightType, [$stringType]))
            || ($this->containsOnlyTypes($leftType, [$dateTimeType]) && $this->containsOnlyTypes($rightType, [$dateTimeType]));
    }

    /**
     * @param Type[] $allowedTypes
     */
    private function containsOnlyTypes(Type $checkedType, array $allowedTypes): bool
    {
        $typesToCheck = $checkedType instanceof UnionType
            ? $checkedType->getTypes()
            : [$checkedType];

        foreach ($typesToCheck as $typeToCheck) {
            $isWithinAllowed = false;

            foreach ($allowedTypes as $allowedType) {
                if ($allowedType->isSuperTypeOf($typeToCheck)->yes()) {
                    $isWithinAllowed = true;
                    break;
                }
            }

            if (!$isWithinAllowed) {
                return false;
            }
        }

        return true;
    }

}
