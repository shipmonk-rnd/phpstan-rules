<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Expr\Throw_;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function count;
use function implode;
use function in_array;
use function sprintf;

/**
 * @implements Rule<ReturnStatementsNode>
 */
class EnforceNativeReturnTypehintRule implements Rule
{

    private FileTypeMapper $fileTypeMapper;

    private PhpVersion $phpVersion;

    private bool $treatPhpDocTypesAsCertain;

    public function __construct(
        FileTypeMapper $fileTypeMapper,
        PhpVersion $phpVersion,
        bool $treatPhpDocTypesAsCertain
    )
    {
        $this->fileTypeMapper = $fileTypeMapper;
        $this->phpVersion = $phpVersion;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
    }

    public function getNodeType(): string
    {
        return ReturnStatementsNode::class;
    }

    /**
     * @param ReturnStatementsNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        if ($this->treatPhpDocTypesAsCertain === false) {
            return [];
        }

        if ($node->hasNativeReturnTypehint()) {
            return [];
        }

        if ($node instanceof PropertyHookReturnStatementsNode) {
            return []; // hooks cannot have native return typehints
        }

        if (!$scope->isInAnonymousFunction() && in_array($scope->getFunctionName(), ['__construct', '__destruct', '__clone'], true)) {
            return [];
        }

        if ($scope->isInTrait()) {
            return []; // return may easily differ for each usage
        }

        $phpDocReturnType = $this->getPhpDocReturnType($node, $scope);
        $returnType = $phpDocReturnType ?? $this->getTypeOfReturnStatements($node);
        $alwaysThrows = $this->alwaysThrowsException($node);

        $typeHint = $this->getTypehintByType($returnType, $scope, $phpDocReturnType !== null, $alwaysThrows, true);

        if ($typeHint === null) {
            return [];
        }

        $error = RuleErrorBuilder::message(sprintf('Missing native return typehint %s', $typeHint))
            ->identifier('shipmonk.missingNativeReturnTypehint')
            ->build();
        return [$error];
    }

    private function getTypehintByType(
        Type $type,
        Scope $scope,
        bool $typeFromPhpDoc,
        bool $alwaysThrowsException,
        bool $topLevel
    ): ?string
    {
        if ($type instanceof MixedType) {
            return $this->phpVersion->getVersionId() >= 80_000 ? 'mixed' : null;
        }

        if ($type->isVoid()->yes()) {
            return 'void';
        }

        if ($type instanceof NeverType) {
            if (($typeFromPhpDoc || $alwaysThrowsException) && $this->phpVersion->getVersionId() >= 80_100) {
                return 'never';
            }

            return 'void';
        }

        if ($type->isNull()->yes()) {
            if (!$topLevel || $this->phpVersion->getVersionId() >= 80_200) {
                return 'null';
            }

            return null;
        }

        $typeWithoutNull = TypeCombinator::removeNull($type);
        $typeHint = null;

        if ((new BooleanType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            if (($typeWithoutNull->isTrue()->yes() || $typeWithoutNull->isFalse()->yes()) && $this->phpVersion->getVersionId() >= 80_200) {
                $typeHint = $typeWithoutNull->describe(VerbosityLevel::typeOnly());
            } else {
                $typeHint = 'bool';
            }
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
        } elseif (count($typeWithoutNull->getObjectClassNames()) === 1) {
            $className = $typeWithoutNull->getObjectClassNames()[0];

            if ($className === $this->getClassName($scope)) {
                $typeHint = 'self';
            } else {
                $typeHint = '\\' . $className;
            }
        } elseif ((new CallableType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'callable';
        } elseif ((new IterableType(new MixedType(), new MixedType()))->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'iterable';
        } elseif ($this->getUnionTypehint($type, $scope, $typeFromPhpDoc, $alwaysThrowsException) !== null) {
            return $this->getUnionTypehint($type, $scope, $typeFromPhpDoc, $alwaysThrowsException);
        } elseif ($this->getIntersectionTypehint($type, $scope, $typeFromPhpDoc, $alwaysThrowsException) !== null) {
            return $this->getIntersectionTypehint($type, $scope, $typeFromPhpDoc, $alwaysThrowsException);
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

    private function getPhpDocReturnType(
        Node $node,
        Scope $scope
    ): ?Type
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
    ): ?string
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

            if ($subtype instanceof IntersectionType) { // @phpstan-ignore phpstanApi.instanceofType
                if ($this->phpVersion->getVersionId() < 80_200) { // DNF
                    return null;
                }

                $wrap = true;
            }

            $subtypeHint = $this->getTypehintByType($subtype, $scope, $typeFromPhpDoc, $alwaysThrowsException, false);

            if ($subtypeHint === null) {
                return null;
            }

            if (in_array($subtypeHint, $typehintParts, true)) {
                continue;
            }

            $typehintParts[] = $wrap ? "($subtypeHint)" : $subtypeHint;
        }

        return implode('|', $typehintParts);
    }

    private function getIntersectionTypehint(
        Type $type,
        Scope $scope,
        bool $typeFromPhpDoc,
        bool $alwaysThrowsException
    ): ?string
    {
        if (!$type instanceof IntersectionType) { // @phpstan-ignore phpstanApi.instanceofType
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

            $subtypeHint = $this->getTypehintByType($subtype, $scope, $typeFromPhpDoc, $alwaysThrowsException, false);

            if ($subtypeHint === null) {
                return null;
            }

            if (in_array($subtypeHint, $typehintParts, true)) {
                continue;
            }

            $typehintParts[] = $wrap ? "($subtypeHint)" : $subtypeHint;
        }

        return implode('&', $typehintParts);
    }

    private function alwaysThrowsException(ReturnStatementsNode $node): bool
    {
        $exitPoints = $node->getStatementResult()->getExitPoints();

        foreach ($exitPoints as $exitPoint) {
            $statement = $exitPoint->getStatement();
            $isThrow = $statement instanceof Expression && $statement->expr instanceof Throw_;

            if (!$isThrow) {
                return false;
            }
        }

        return $exitPoints !== [];
    }

}
