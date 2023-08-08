<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use Generator;
use LogicException;
use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Throw_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassMethod;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VoidType;
use ReflectionClass;
use function count;
use function in_array;
use function str_replace;
use function strlen;
use function substr;

/**
 * @implements Rule<ReturnStatementsNode>
 */
class EnforceNativeReturnTypehintRule implements Rule
{

    private FileTypeMapper $fileTypeMapper;

    private PhpVersion $phpVersion;

    private bool $treatPhpDocTypesAsCertain;

    private bool $enforceNarrowestTypehint;

    public function __construct(
        FileTypeMapper $fileTypeMapper,
        PhpVersion $phpVersion,
        bool $treatPhpDocTypesAsCertain,
        bool $enforceNarrowestTypehint = true
    )
    {
        $this->fileTypeMapper = $fileTypeMapper;
        $this->phpVersion = $phpVersion;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
        $this->enforceNarrowestTypehint = $enforceNarrowestTypehint;
    }

    public function getNodeType(): string
    {
        return ReturnStatementsNode::class;
    }

    /**
     * @param ReturnStatementsNode $node
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->treatPhpDocTypesAsCertain === false) {
            return [];
        }

        if (!$scope->isInAnonymousFunction() && in_array($scope->getFunctionName(), ['__construct', '__destruct', '__clone'], true)) {
            return [];
        }

        if ($scope->isInTrait()) {
            return []; // return may easily differ for each usage
        }

        $hasNativeReturnType = $this->hasNativeReturnTypehint($node);

        if ($hasNativeReturnType && !$this->enforceNarrowestTypehint) {
            return [];
        }

        $phpDocReturnType = $this->getPhpDocReturnType($node, $scope);
        $returnType = $phpDocReturnType ?? $this->getTypeOfReturnStatements($node);
        $alwaysThrows = $this->alwaysThrowsException($node);

        $typeHint = $this->getTypehintByType($returnType, $scope, $phpDocReturnType !== null, $alwaysThrows, true);

        if ($typeHint === null) {
            return [];
        }

        if (!$hasNativeReturnType) {
            return ["Missing native return typehint {$this->toTypehint($typeHint)}"];
        }

        if ($this->enforceNarrowestTypehint) {
            $actualReturnType = $this->getTypeOfReturnStatements($node);
            $nativeReturnType = $this->getNativeReturnTypehint($node, $scope);

            $typeHintFromActualReturns = $this->getTypehintByType($actualReturnType, $scope, $phpDocReturnType !== null, $alwaysThrows, true);
            $typeHintFromNativeTypehint = $this->getTypehintByType($nativeReturnType, $scope, $phpDocReturnType !== null, $alwaysThrows, true);

            if (
                $typeHintFromActualReturns !== null
                && $typeHintFromNativeTypehint !== null
                && $typeHintFromNativeTypehint->isSuperTypeOf($typeHintFromActualReturns)->yes()
                && !$typeHintFromNativeTypehint->equals($typeHintFromActualReturns)
            ) {
                return ["Native return typehint is {$this->toTypehint($typeHintFromNativeTypehint)}, but can be narrowed to {$this->toTypehint($typeHintFromActualReturns)}"];
            }
        }

        return [];
    }

    private function getTypehintByType(
        Type $type,
        Scope $scope,
        bool $typeFromPhpDoc,
        bool $alwaysThrowsException,
        bool $topLevel
    ): ?Type
    {
        if ($type instanceof MixedType) {
            return $this->phpVersion->getVersionId() >= 80_000 ? new MixedType() : null;
        }

        if ($type->isVoid()->yes()) {
            return new VoidType();
        }

        if ($type instanceof NeverType) {
            if (($typeFromPhpDoc || $alwaysThrowsException) && $this->phpVersion->getVersionId() >= 80_100) {
                return new NeverType();
            }

            return new VoidType();
        }

        if ($type->isNull()->yes()) {
            if (!$topLevel || $this->phpVersion->getVersionId() >= 80_200) {
                return new NullType();
            }

            return null;
        }

        $typeWithoutNull = TypeCombinator::removeNull($type);
        $typeHint = null;

        if ((new BooleanType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $supportsStandaloneTrue = $this->phpVersion->getVersionId() >= 80_200;

            if ($supportsStandaloneTrue && $typeWithoutNull->isTrue()) {
                $typeHint = new ConstantBooleanType(true);
            } elseif ($supportsStandaloneTrue && $typeWithoutNull->isFalse()) {
                $typeHint = new ConstantBooleanType(false);
            } else {
                $typeHint = new BooleanType();
            }
        } elseif ((new IntegerType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = new IntegerType();

        } elseif ((new FloatType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = new FloatType();

        } elseif ((new ArrayType(new MixedType(), new MixedType()))->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = new ArrayType(new MixedType(), new MixedType());

        } elseif ((new StringType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = new StringType();

        } elseif (count($typeWithoutNull->getObjectClassNames()) === 1) {
            $className = $typeWithoutNull->getObjectClassNames()[0];
            $typeHint = new ObjectType('\\' . $className);

        } elseif ((new CallableType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = new CallableType();

        } elseif ((new IterableType(new MixedType(), new MixedType()))->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = new IterableType(new MixedType(), new MixedType());

        } elseif ($this->getUnionTypehint($type, $scope, $typeFromPhpDoc, $alwaysThrowsException) !== null) {
            return $this->getUnionTypehint($type, $scope, $typeFromPhpDoc, $alwaysThrowsException);

        } elseif ($this->getIntersectionTypehint($type, $scope, $typeFromPhpDoc, $alwaysThrowsException) !== null) {
            return $this->getIntersectionTypehint($type, $scope, $typeFromPhpDoc, $alwaysThrowsException);

        } elseif ((new ObjectWithoutClassType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = new ObjectWithoutClassType();
        }

        if ($typeHint !== null && TypeCombinator::containsNull($type)) {
            $typeHint = TypeCombinator::addNull($typeHint);
        }

        return $typeHint;
    }

    private function getTypeOfReturnStatements(ReturnStatementsNode $node): Type
    {
        if ($node->getStatementResult()->hasYield()) {
            return new ObjectType(Generator::class);
        }

        $types = [];

        foreach ($node->getReturnStatements() as $returnStatement) {
            $returnNode = $returnStatement->getReturnNode();

            if ($returnNode->expr !== null) {
                $types[] = $returnStatement->getScope()->getType($returnNode->expr);
            }
        }

        return TypeCombinator::union(...$types);
    }

    private function getNativeReturnTypehint(ReturnStatementsNode $node, Scope $scope): Type
    {
        $reflection = new ReflectionClass($node);

        if ($node instanceof MethodReturnStatementsNode) { // @phpstan-ignore-line ignore bc warning
            $classMethodReflection = $reflection->getProperty('classMethod');
            $classMethodReflection->setAccessible(true);
            /** @var ClassMethod $classMethod */
            $classMethod = $classMethodReflection->getValue($node);
            return $scope->getFunctionType($classMethod->returnType, $classMethod->returnType === null, false);
        }

        if ($node instanceof FunctionReturnStatementsNode) { // @phpstan-ignore-line ignore bc warning
            $functionReflection = $reflection->getProperty('function');
            $functionReflection->setAccessible(true);
            /** @var Function_ $function */
            $function = $functionReflection->getValue($node);
            return $scope->getFunctionType($function->returnType, $function->returnType === null, false);
        }

        if ($node instanceof ClosureReturnStatementsNode) { // @phpstan-ignore-line ignore bc warning
            $closureReflection = $reflection->getProperty('closureExpr');
            $closureReflection->setAccessible(true);
            /** @var Closure $closure */
            $closure = $closureReflection->getValue($node);
            return $scope->getFunctionType($closure->returnType, $closure->returnType === null, false);
        }

        throw new LogicException('Unexpected subtype');
    }

    /**
     * To be removed once we bump phpstan version to 1.9.5+ (https://github.com/phpstan/phpstan-src/pull/2141)
     */
    private function hasNativeReturnTypehint(ReturnStatementsNode $node): bool
    {
        if ($node instanceof MethodReturnStatementsNode) { // @phpstan-ignore-line ignore bc warning
            return $node->hasNativeReturnTypehint();
        }

        if ($node instanceof FunctionReturnStatementsNode) { // @phpstan-ignore-line ignore bc warning
            return $node->hasNativeReturnTypehint();
        }

        if ($node instanceof ClosureReturnStatementsNode) { // @phpstan-ignore-line ignore bc warning
            return $node->getClosureExpr()->returnType !== null;
        }

        throw new LogicException('Unexpected subtype');
    }

    private function getPhpDocReturnType(Node $node, Scope $scope): ?Type
    {
        $docComment = $node->getDocComment();

        if ($docComment === null) {
            return null;
        }

        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $scope->getClassReflection() === null ? null : $scope->getClassReflection()->getName(),
            $scope->getTraitReflection() === null ? null : $scope->getTraitReflection()->getName(),
            $scope->getFunctionName(),
            $docComment->getText(),
        );

        $returnTag = $resolvedPhpDoc->getReturnTag();

        if ($returnTag === null) {
            return null;
        }

        return $returnTag->getType();
    }

    private function getClassName(Scope $scope): ?string
    {
        if ($scope->getClassReflection() === null) {
            return null;
        }

        return $scope->getClassReflection()->getName();
    }

    private function getUnionTypehint(
        Type $type,
        Scope $scope,
        bool $typeFromPhpDoc,
        bool $alwaysThrowsException
    ): ?Type
    {
        if (!$type instanceof UnionType) {
            return null;
        }

        if (!$this->phpVersion->supportsNativeUnionTypes()) {
            return null;
        }

        $typehintParts = [];

        foreach ($type->getTypes() as $subtype) {
            if ($subtype instanceof IntersectionType) { // @phpstan-ignore-line ignore instanceof intersection
                if ($this->phpVersion->getVersionId() < 80_200) { // DNF
                    return null;
                }
            }

            $subtypeHint = $this->getTypehintByType($subtype, $scope, $typeFromPhpDoc, $alwaysThrowsException, false);

            if ($subtypeHint === null) {
                return null;
            }

            if (in_array($subtypeHint, $typehintParts, true)) {
                continue;
            }

            $typehintParts[] = $subtypeHint;
        }

        return TypeCombinator::union(...$typehintParts);
    }

    private function getIntersectionTypehint(
        Type $type,
        Scope $scope,
        bool $typeFromPhpDoc,
        bool $alwaysThrowsException
    ): ?Type
    {
        if (!$type instanceof IntersectionType) { // @phpstan-ignore-line ignore instanceof intersection
            return null;
        }

        if (!$this->phpVersion->supportsPureIntersectionTypes()) {
            return null;
        }

        $typehintParts = [];

        foreach ($type->getTypes() as $subtype) {
            if ($subtype instanceof UnionType) {
                if ($this->phpVersion->getVersionId() < 80_200) { // DNF
                    return null;
                }
            }

            $subtypeHint = $this->getTypehintByType($subtype, $scope, $typeFromPhpDoc, $alwaysThrowsException, false);

            if ($subtypeHint === null) {
                return null;
            }

            if (in_array($subtypeHint, $typehintParts, true)) {
                continue;
            }

            $typehintParts[] = $subtypeHint;
        }

        return TypeCombinator::intersect(...$typehintParts);
    }

    private function alwaysThrowsException(ReturnStatementsNode $node): bool
    {
        if (count($node->getReturnStatements()) > 0) {
            return false;
        }

        foreach ($node->getExecutionEnds() as $executionEnd) {
            if (!$executionEnd->getNode() instanceof Throw_) {
                return false;
            }
        }

        return $node->getExecutionEnds() !== [];
    }

    private function toTypehint(Type $type): string
    {
        if (TypeCombinator::containsNull($type) && $type instanceof UnionType && count($type->getTypes()) === 2) {
            $typeWithoutNull = TypeCombinator::removeNull($type);
            return '?' . $typeWithoutNull->toPhpDocNode();
        }

        $typeHint = str_replace(' ', '', (string) $type->toPhpDocNode());

        if ($typeHint[0] === '(' && $typeHint[strlen($typeHint) - 1] === ')') {
            return substr($typeHint, 1, strlen($typeHint) - 2);
        }

        return $typeHint;
    }

}
