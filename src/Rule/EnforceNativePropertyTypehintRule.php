<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
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
 * @implements Rule<Property>
 */
class EnforceNativePropertyTypehintRule implements Rule
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
        return Property::class;
    }

    /**
     * @param Property $node
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

        if ($node->type !== null) {
            return [];
        }

        if ($scope->isInTrait()) {
            return []; // type may easily differ for each usage
        }

        if ($this->phpVersion->getVersionId() < 70_400) {
            return []; // typed properties not available
        }

        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return [];
        }

        $phpDocType = $this->getPhpDocVarType($node, $scope);

        if ($phpDocType === null) {
            return [];
        }

        $errors = [];

        foreach ($node->props as $prop) {
            $propertyName = $prop->name->name;

            if ($this->isPropertyDeclaredInParent($propertyName, $scope)) {
                continue; // avoid LSP issues
            }

            $typeHint = $this->getTypehintByType($phpDocType, $scope, true);

            if ($typeHint === null) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf('Missing native property typehint %s', $typeHint))
                ->identifier('shipmonk.missingNativePropertyTypehint')
                ->line($prop->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function getPhpDocVarType(Property $node, Scope $scope): ?Type
    {
        $docComment = $node->getDocComment();

        if ($docComment === null) {
            return null;
        }

        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return null;
        }

        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $classReflection->getName(),
            $scope->getTraitReflection() === null ? null : $scope->getTraitReflection()->getName(),
            null,
            $docComment->getText(),
        );

        $varTags = $resolvedPhpDoc->getVarTags();

        foreach ($varTags as $varTag) {
            return $varTag->getType();
        }

        return null;
    }

    private function isPropertyDeclaredInParent(string $propertyName, Scope $scope): bool
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return false;
        }

        $parent = $classReflection->getParentClass();

        while ($parent !== null) {
            if ($parent->hasNativeProperty($propertyName)) {
                return true;
            }

            $parent = $parent->getParentClass();
        }

        return false;
    }

    private function getTypehintByType(
        Type $type,
        Scope $scope,
        bool $topLevel
    ): ?string
    {
        if ($type instanceof MixedType || $this->isUnionTypeWithMixed($type)) {
            return $this->phpVersion->getVersionId() >= 80_000 ? 'mixed' : null;
        }

        if ($type->isNull()->yes()) {
            if (!$topLevel || $this->phpVersion->getVersionId() >= 80_200) {
                return 'null';
            }

            return null;
        }

        // Check union types first (handles nullable unions properly)
        if ($type instanceof UnionType) {
            return $this->getUnionTypehint($type, $scope);
        }

        // Check intersection types before other type checks
        if ($type instanceof IntersectionType) { // @phpstan-ignore phpstanApi.instanceofType
            return $this->getIntersectionTypehint($type, $scope);
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
            $typeHint = 'self'; // properties cannot use static keyword
        } elseif (count($typeWithoutNull->getObjectClassNames()) === 1) {
            $className = $typeWithoutNull->getObjectClassNames()[0];

            if ($className === $this->getClassName($scope)) {
                $typeHint = 'self';
            } else {
                $typeHint = '\\' . $className;
            }
        } elseif ((new IterableType(new MixedType(), new MixedType()))->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'iterable';
        } elseif ((new ObjectWithoutClassType())->accepts($typeWithoutNull, $scope->isDeclareStrictTypes())->yes()) {
            $typeHint = 'object';
        }

        if ($typeHint !== null && TypeCombinator::containsNull($type)) {
            $typeHint = '?' . $typeHint;
        }

        return $typeHint;
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
        Scope $scope
    ): ?string
    {
        if (!$type instanceof UnionType) {
            return null;
        }

        // Handle nullable types specially for PHP < 8.0
        if (!$this->phpVersion->supportsNativeUnionTypes()) {
            // Check if this is a simple nullable type (T|null with exactly 2 types)
            $types = $type->getTypes();

            if (count($types) === 2 && TypeCombinator::containsNull($type)) {
                $typeWithoutNull = TypeCombinator::removeNull($type);
                $innerHint = $this->getTypehintByType($typeWithoutNull, $scope, false);

                if ($innerHint !== null) {
                    return '?' . $innerHint;
                }
            }

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

            $subtypeHint = $this->getTypehintByType($subtype, $scope, false);

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
        Scope $scope
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

            $subtypeHint = $this->getTypehintByType($subtype, $scope, false);

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

    private function isUnionTypeWithMixed(Type $type): bool
    {
        if (!$type instanceof UnionType) {
            return false;
        }

        foreach ($type->getTypes() as $innerType) {
            if ($innerType instanceof MixedType) {
                return true;
            }
        }

        return false;
    }

}
