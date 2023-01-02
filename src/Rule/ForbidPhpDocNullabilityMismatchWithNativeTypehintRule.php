<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Rules\Rule;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use function array_merge;
use function is_string;

/**
 * @implements Rule<Node>
 */
class ForbidPhpDocNullabilityMismatchWithNativeTypehintRule implements Rule
{

    private FileTypeMapper $fileTypeMapper;

    public function __construct(
        FileTypeMapper $fileTypeMapper
    )
    {
        $this->fileTypeMapper = $fileTypeMapper;
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node instanceof FunctionLike) {
            return [
                ...$this->checkReturnTypes($node, $scope),
                ...$this->checkParamTypes($node, $scope),
            ];
        }

        if ($node instanceof Property) {
            return $this->checkPropertyTypes($node, $scope);
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function checkReturnTypes(FunctionLike $node, Scope $scope): array
    {
        $phpDocReturnType = $this->getFunctionPhpDocReturnType($node, $scope);
        $nativeReturnType = $this->getFunctionNativeReturnType($node, $scope);

        return $this->comparePhpDocAndNativeType($phpDocReturnType, $nativeReturnType, $scope, '@return');
    }

    /**
     * @return list<string>
     */
    private function checkPropertyTypes(Property $node, Scope $scope): array
    {
        $phpDocReturnType = $this->getPropertyPhpDocType($node, $scope);
        $nativeReturnType = $this->getPropertyNativeType($node, $scope);

        return $this->comparePhpDocAndNativeType($phpDocReturnType, $nativeReturnType, $scope, '@var');
    }

    /**
     * @return list<string>
     */
    private function checkParamTypes(FunctionLike $node, Scope $scope): array
    {
        $errors = [];

        foreach ($node->getParams() as $param) {
            if (!$param->var instanceof Variable || !is_string($param->var->name)) {
                continue;
            }

            $paramName = $param->var->name;

            $phpDocParamType = $this->getPhpDocParamType($node, $scope, $paramName);
            $nativeParamType = $scope->getFunctionType($param->type, false, false);

            $errors = array_merge(
                $errors,
                $this->comparePhpDocAndNativeType($phpDocParamType, $nativeParamType, $scope, "@param \$$paramName"),
            );
        }

        return $errors;
    }

    private function getPropertyNativeType(Property $node, Scope $scope): ?Type
    {
        if ($node->type === null) {
            return null;
        }

        return $scope->getFunctionType($node->type, false, false);
    }

    private function getFunctionNativeReturnType(FunctionLike $node, Scope $scope): ?Type
    {
        if ($node->getReturnType() === null) {
            return null;
        }

        return $scope->getFunctionType($node->getReturnType(), false, false);
    }

    private function getPropertyPhpDocType(Property $node, Scope $scope): ?Type
    {
        $resolvedPhpDoc = $this->resolvePhpDoc($node, $scope);

        if ($resolvedPhpDoc === null) {
            return null;
        }

        $varTags = $resolvedPhpDoc->getVarTags();

        foreach ($varTags as $varTag) {
            return $varTag->getType();
        }

        return null;
    }

    private function getFunctionPhpDocReturnType(FunctionLike $node, Scope $scope): ?Type
    {
        $resolvedPhpDoc = $this->resolvePhpDoc($node, $scope);

        if ($resolvedPhpDoc === null) {
            return null;
        }

        $returnTag = $resolvedPhpDoc->getReturnTag();

        if ($returnTag === null) {
            return null;
        }

        return $returnTag->getType();
    }

    private function resolvePhpDoc(Node $node, Scope $scope): ?ResolvedPhpDocBlock
    {
        $docComment = $node->getDocComment();

        if ($docComment === null) {
            return null;
        }

        return $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $scope->getClassReflection() === null ? null : $scope->getClassReflection()->getName(),
            $scope->getTraitReflection() === null ? null : $scope->getTraitReflection()->getName(),
            $scope->getFunctionName(),
            $docComment->getText(),
        );
    }

    private function getPhpDocParamType(FunctionLike $node, Scope $scope, string $parameterName): ?Type
    {
        $resolvedPhpDoc = $this->resolvePhpDoc($node, $scope);

        if ($resolvedPhpDoc === null) {
            return null;
        }

        $paramTags = $resolvedPhpDoc->getParamTags();

        foreach ($paramTags as $paramTagName => $paramTag) {
            if ($paramTagName === $parameterName) {
                return $paramTag->getType();
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function comparePhpDocAndNativeType(?Type $phpDocReturnType, ?Type $nativeReturnType, Scope $scope, string $phpDocIdentification): array
    {
        if ($phpDocReturnType === null || $nativeReturnType === null) {
            return [];
        }

        $strictTypes = $scope->isDeclareStrictTypes();
        $nullType = new NullType();

        // the inverse check is performed by native PHPStan rule checking that phpdoc is subtype of native type
        if (!$phpDocReturnType->accepts($nullType, $strictTypes)->yes() && $nativeReturnType->accepts($nullType, $strictTypes)->yes()) {
            return ["The $phpDocIdentification phpdoc does not contain null, but native return type does"];
        }

        return [];
    }

}
