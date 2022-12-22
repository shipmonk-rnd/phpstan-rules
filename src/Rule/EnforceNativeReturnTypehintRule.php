<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
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
use PHPStan\Type\ResourceType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\VoidType;
use function implode;
use function sprintf;
use const PHP_VERSION_ID;

/**
 * @implements Rule<ClassMethod>
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
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->treatPhpDocTypesAsCertain === false) {
            return [];
        }

        if ($node->returnType !== null) {
            return [];
        }

        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return [];
        }

        $docComment = $node->getDocComment();

        if ($docComment === null) {
            return [];
        }

        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $classReflection->getName(),
            $scope->getTraitReflection() === null ? null : $scope->getTraitReflection()->getName(),
            $scope->getFunctionName(),
            $docComment->getText(),
        );

        $returnTag = $resolvedPhpDoc->getReturnTag();

        if ($returnTag === null) {
            return [];
        }

        $typeHint = $this->getTypehintByType($returnTag->getType(), $scope, true);

        if ($typeHint === null) {
            return [];
        }

        return [
            sprintf('Missing native return typehint %s', $typeHint),
        ];
    }

    private function getTypehintByType(Type $type, Scope $scope, bool $topLevel): ?string
    {
        if ($type instanceof MixedType) {
            return 'mixed';
        }

        if ($type instanceof VoidType) {
            return 'void';
        }

        if ($type instanceof NeverType) {
            return 'never';
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
        } elseif ((new CallableType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'callable'; // prefer callable over \Closure
        } elseif ((new IntegerType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'int';
        } elseif ((new FloatType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'float';
        } elseif ((new ArrayType(new MixedType(), new MixedType()))->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'array';
        } elseif ((new IterableType(new MixedType(), new MixedType()))->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'iterable';
        } elseif ((new StringType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'string';
        } elseif ($type instanceof TypeWithClassName) {
            $typeHint = '\\' . $typeWithoutNull->describe(VerbosityLevel::typeOnly());
        }

        if ($typeHint !== null && TypeCombinator::containsNull($type)) {
            $typeHint = '?' . $typeHint;
        }

        if ($typeHint === null) {
            if ($type instanceof UnionType) {
                if (!$this->phpVersion->supportsNativeUnionTypes()) {
                    return null;
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

                    $subtypeHint = $this->getTypehintByType($subtype, $scope, false);
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

                    $subtypeHint = $this->getTypehintByType($subtype, $scope, false);
                    $typehintParts[] = $wrap ? "($subtypeHint)" : $subtypeHint;
                }

                return implode('&', $typehintParts);
            }
        }

        return $typeHint;
    }

}
