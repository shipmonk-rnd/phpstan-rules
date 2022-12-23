<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use Generator;
use LogicException;
use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
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
use PHPStan\Type\ResourceType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use ReflectionClass;
use function implode;
use function in_array;

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

        $alwaysTerminating = $node->getStatementResult()->isAlwaysTerminating();
        $phpDocReturnType = $this->getPhpDocReturnType($node, $scope);
        $returnType = $phpDocReturnType ?? $this->getTypeOfReturnStatements($node);

        $typeHint = $this->getTypehintByType($returnType, $scope, $phpDocReturnType !== null, $alwaysTerminating, true);

        if ($typeHint === null) {
            return [];
        }

        if (!$hasNativeReturnType) {
            return ["Missing native return typehint $typeHint"];
        }

        if ($this->enforceNarrowestTypehint) {
            $nativeReturnType = $this->getNativeReturnTypehint($node, $scope);
            $typeHintFromNativeTypehint = $this->getTypehintByType($nativeReturnType, $scope, $phpDocReturnType !== null, $alwaysTerminating, true);

            if ($typeHintFromNativeTypehint !== $typeHint) {
                return ["Native return typehint is $typeHintFromNativeTypehint, but can be narrowed to $typeHint"];
            }
        }

        return [];
    }

    private function getTypehintByType(
        Type $type,
        Scope $scope,
        bool $typeFromPhpDoc,
        bool $alwaysTerminating,
        bool $topLevel
    ): ?string
    {
        if ($type instanceof MixedType) {
            return $this->phpVersion->getVersionId() >= 80_000 ? 'mixed' : null;
        }

        if ($type instanceof VoidType) {
            return 'void';
        }

        if ($type instanceof NeverType) {
            if (($typeFromPhpDoc || $alwaysTerminating) && $this->phpVersion->getVersionId() >= 80_100) {
                return 'never';
            }

            return 'void';
        }

        if ($type instanceof NullType) {
            if (!$topLevel || $this->phpVersion->getVersionId() >= 80_200) {
                return 'null';
            }

            return null;
        }

        $typeWithoutNull = TypeCombinator::removeNull($type);
        $typeHint = null;

        if ((new BooleanType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            if ($typeWithoutNull instanceof ConstantBooleanType && $this->phpVersion->getVersionId() >= 80_200) {
                $typeHint = $typeWithoutNull->describe(VerbosityLevel::typeOnly());
            } else {
                $typeHint = 'bool';
            }
        } elseif ((new ResourceType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'resource';
        } elseif ((new IntegerType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'int';
        } elseif ((new FloatType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'float';
        } elseif ((new ArrayType(new MixedType(), new MixedType()))->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'array';
        } elseif ((new StringType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'string';
        } elseif ($typeWithoutNull instanceof StaticType) {
            if ($this->phpVersion->getVersionId() < 80_000) {
                $typeHint = 'self';
            } else {
                $typeHint = 'static';
            }
        } elseif ($typeWithoutNull instanceof TypeWithClassName) {
            if ($typeWithoutNull->getClassName() === $this->getClassName($scope)) {
                $typeHint = 'self';
            } else {
                $typeHint = '\\' . $typeWithoutNull->getClassName();
            }
        } elseif ((new CallableType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'callable';
        } elseif ((new IterableType(new MixedType(), new MixedType()))->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'iterable';
        } elseif ($this->getUnionTypehint($type, $scope, $typeFromPhpDoc, $alwaysTerminating) !== null) {
            return $this->getUnionTypehint($type, $scope, $typeFromPhpDoc, $alwaysTerminating);
        } elseif ($this->getIntersectionTypehint($type, $scope, $typeFromPhpDoc, $alwaysTerminating) !== null) {
            return $this->getIntersectionTypehint($type, $scope, $typeFromPhpDoc, $alwaysTerminating);
        } elseif ((new ObjectWithoutClassType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'object';
        }

        if ($typeHint !== null && TypeCombinator::containsNull($type)) {
            $typeHint = '?' . $typeHint;
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
            $function = functionReflection->getValue($node);
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

    private function getUnionTypehint(Type $type, Scope $scope, bool $typeFromPhpDoc, bool $alwaysTerminating): ?string
    {
        if (!$type instanceof UnionType) {
            return null;
        }

        if (!$this->phpVersion->supportsNativeUnionTypes()) {
            return null;
        }

        $typehintParts = [];

        foreach ($type->getTypes() as $subtype) {
            $wrap = false;

            if ($subtype instanceof IntersectionType) {
                if ($this->phpVersion->getVersionId() < 80_200) { // DNF
                    return null;
                }

                $wrap = true;
            }

            $subtypeHint = $this->getTypehintByType($subtype, $scope, $typeFromPhpDoc, $alwaysTerminating, false);

            if ($subtypeHint === null) {
                return null;
            }

            $typehintParts[] = $wrap ? "($subtypeHint)" : $subtypeHint;
        }

        return implode('|', $typehintParts);
    }

    private function getIntersectionTypehint(Type $type, Scope $scope, bool $typeFromPhpDoc, bool $alwaysTerminating): ?string
    {
        if (!$type instanceof IntersectionType) {
            return null;
        }

        if (!$this->phpVersion->supportsPureIntersectionTypes()) {
            return null;
        }

        $typehintParts = [];

        foreach ($type->getTypes() as $subtype) {
            $wrap = false;

            if ($subtype instanceof UnionType) {
                if ($this->phpVersion->getVersionId() < 80_200) { // DNF
                    return null;
                }

                $wrap = true;
            }

            $subtypeHint = $this->getTypehintByType($subtype, $scope, $typeFromPhpDoc, $alwaysTerminating, false);

            if ($subtypeHint === null) {
                return null;
            }

            $typehintParts[] = $wrap ? "($subtypeHint)" : $subtypeHint;
        }

        return implode('&', $typehintParts);
    }

}
