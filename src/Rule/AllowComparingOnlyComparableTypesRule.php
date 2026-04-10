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
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function count;
use const PHP_VERSION_ID;

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
    public function processNode(
        Node $node,
        Scope $scope,
    ): array
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

        $leftTypeDescribed = $leftType->describe($leftType->isArray()->no() ? VerbosityLevel::typeOnly() : VerbosityLevel::value());
        $rightTypeDescribed = $rightType->describe($rightType->isArray()->no() ? VerbosityLevel::typeOnly() : VerbosityLevel::value());

        if (!$this->isComparable($leftType) || !$this->isComparable($rightType)) {
            $builder = RuleErrorBuilder::message("Comparison {$leftTypeDescribed} {$node->getOperatorSigil()} {$rightTypeDescribed} contains non-comparable type, only int|float|string|DateTimeInterface or comparable tuple is allowed.")
                ->identifier('shipmonk.comparingNonComparableTypes');
            if (PHP_VERSION_ID >= 80_400) {
                $builder->addTip('Also BcMath\Number is allowed.');
            }
            $error = $builder->build();
            return [$error];
        }

        if (!$this->isComparableTogether($leftType, $rightType)) {
            $error = RuleErrorBuilder::message("Cannot compare different types in {$leftTypeDescribed} {$node->getOperatorSigil()} {$rightTypeDescribed}.")
                ->identifier('shipmonk.comparingNonComparableTypes')
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
        $bcMathNumberType = new ObjectType('BcMath\Number');

        if ($this->containsOnlyTypes($type, [$intType, $floatType, $stringType, $dateTimeType, $bcMathNumberType])) {
            return true;
        }

        if (!$type->isConstantArray()->yes() || !$type->isList()->yes()) {
            return false;
        }

        foreach ($type->getConstantArrays() as $constantArray) {
            foreach ($constantArray->getValueTypes() as $valueType) {
                if (!$this->isComparable($valueType)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function isComparableTogether(
        Type $leftType,
        Type $rightType,
    ): bool
    {
        $intType = new IntegerType();
        $floatType = new FloatType();
        $stringType = new StringType();
        $dateTimeType = new ObjectType(DateTimeInterface::class);
        $bcMathNumberType = new ObjectType('BcMath\Number');

        if ($this->containsOnlyTypes($leftType, [$bcMathNumberType, $intType])
            && $this->containsOnlyTypes($rightType, [$bcMathNumberType, $intType])) {
            return true;
        }

        if ($this->containsOnlyTypes($leftType, [$intType, $floatType])) {
            return $this->containsOnlyTypes($rightType, [$intType, $floatType]);
        }

        if ($this->containsOnlyTypes($leftType, [$stringType])) {
            return $this->containsOnlyTypes($rightType, [$stringType]);
        }

        if ($this->containsOnlyTypes($leftType, [$dateTimeType])) {
            return $this->containsOnlyTypes($rightType, [$dateTimeType]);
        }

        if ($leftType->isConstantArray()->yes()) {
            if (!$rightType->isConstantArray()->yes()) {
                return false;
            }

            foreach ($leftType->getConstantArrays() as $leftConstantArray) {
                foreach ($rightType->getConstantArrays() as $rightConstantArray) {
                    $leftValueTypes = $leftConstantArray->getValueTypes();
                    $rightValueTypes = $rightConstantArray->getValueTypes();

                    if (count($leftValueTypes) !== count($rightValueTypes)) {
                        return false;
                    }

                    foreach ($leftValueTypes as $i => $leftValueType) {
                        if (!isset($rightValueTypes[$i])) {
                            return false;
                        }

                        if (!$this->isComparableTogether($leftValueType, $rightValueTypes[$i])) {
                            return false;
                        }
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param Type[] $allowedTypes
     */
    private function containsOnlyTypes(
        Type $checkedType,
        array $allowedTypes,
    ): bool
    {
        $allowedType = TypeCombinator::union(...$allowedTypes);
        return $allowedType->isSuperTypeOf($checkedType)->yes();
    }

}
