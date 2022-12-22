<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use Closure;
use Generator;
use LogicException;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClosureReturnStatementsNode;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Node\ReturnStatementsNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
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
use PHPStan\Type\ResourceType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use PHPStan\Type\VoidType;
use function implode;
use function in_array;
use function sprintf;
use const PHP_VERSION_ID;

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
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->treatPhpDocTypesAsCertain === false) {
            return [];
        }

        if ($this->hasNativeReturnTypehint($node)) {
            return [];
        }

        if (!$scope->isInAnonymousFunction() && in_array($scope->getFunctionName(), ['__construct', '__destruct', '__clone'], true)) {
            return [];
        }

        $phpDocReturnType = $this->getPhpDocReturnType($node, $scope);
        $returnType = $phpDocReturnType ?? $this->getTypeOfReturnStatements($node);

        if ($scope->isInTrait()) {
            return []; // return may easily differ for each usage
        }

        $typeHint = $this->getTypehintByType($returnType, $scope, $phpDocReturnType !== null, true);

        if ($typeHint === null) {
            return [];
        }

        return [
            sprintf('Missing native return typehint %s', $typeHint),
        ];
    }

    private function getTypehintByType(Type $type, Scope $scope, bool $typeFromPhpDoc, bool $topLevel): ?string
    {
        if ($type instanceof MixedType) {
            return null; // TODO questionable, adds almost no value
        }

        if ($type instanceof VoidType) {
            return 'void';
        }

        if ($type instanceof NeverType) {
            if ($typeFromPhpDoc) {
                return 'never';
            }

            return 'void';
        }

        if ($type instanceof NullType) {
            return $topLevel ? null : 'null';
        }

        $typeWithoutNull = TypeCombinator::removeNull($type);

        $typeHint = null;

        if ((new BooleanType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            if ($typeWithoutNull instanceof ConstantBooleanType && PHP_VERSION_ID >= 80_200) {
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
        } elseif ((new ObjectType(Generator::class))->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = Generator::class;
        } elseif ((new ObjectType(Closure::class))->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = Closure::class;
        } elseif ((new CallableType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'callable';
        } elseif ((new IterableType(new MixedType(), new MixedType()))->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'iterable';
        } elseif ((new StringType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'string';
        } elseif ($typeWithoutNull instanceof StaticType) {
            if (PHP_VERSION_ID < 80_000) {
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
        }

        if ($typeHint !== null && TypeCombinator::containsNull($type)) {
            $typeHint = '?' . $typeHint;
        }

        if ($typeHint === null) {
            if ($type instanceof UnionType) {
                if (!$this->phpVersion->supportsNativeUnionTypes()) {
                    return null; // TODO enforce object when union not supported (?)
                }

                $typehintParts = [];

                foreach ($type->getTypes() as $subtype) {
                    $wrap = false;

                    if ($subtype instanceof IntersectionType) {
                        if (PHP_VERSION_ID < 80_200) {
                            return null;
                        }

                        $wrap = true;
                    }

                    $subtypeHint = $this->getTypehintByType($subtype, $scope, $typeFromPhpDoc, false);

                    if ($subtypeHint === null) {
                        return null;
                    }

                    $typehintParts[] = $wrap ? "($subtypeHint)" : $subtypeHint;
                }

                return implode('|', $typehintParts);
            }

            if ($type instanceof IntersectionType) {
                if (!$this->phpVersion->supportsPureIntersectionTypes()) {
                    return null;
                }

                $typehintParts = [];

                foreach ($type->getTypes() as $subtype) {
                    $wrap = false;

                    if ($subtype instanceof UnionType) {
                        if (PHP_VERSION_ID < 80_200) {
                            return null;
                        }

                        $wrap = true;
                    }

                    $subtypeHint = $this->getTypehintByType($subtype, $scope, $typeFromPhpDoc, false);

                    if ($subtypeHint === null) {
                        return null;
                    }

                    $typehintParts[] = $wrap ? "($subtypeHint)" : $subtypeHint;
                }

                return implode('&', $typehintParts);
            }
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

}
