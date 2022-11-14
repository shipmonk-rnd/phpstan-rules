<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\Rules\Rule;
use PHPStan\Type\ArrayType;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use function is_string;
use function mb_strpos;

/**
 * @implements Rule<Assign>
 */
class ForbidAssignmentNotMatchingVarDocRule implements Rule
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
        return Assign::class;
    }

    /**
     * @param Assign $node
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $checkShapeOnly = false;
        $allowNarrowing = false;
        $phpDoc = $node->getDocComment();

        if ($phpDoc === null) {
            return [];
        }

        if (mb_strpos($phpDoc->getText(), 'check-shape-only') !== false) {
            $checkShapeOnly = true; // this is needed for example when phpstan-doctrine deduces nullable field, but you added WHERE IS NOT NULL
        }

        if (mb_strpos($phpDoc->getText(), 'allow-narrowing') !== false) {
            $allowNarrowing = true;
        }

        $phpDocBlock = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $scope->getClassReflection() !== null ? $scope->getClassReflection()->getName() : null,
            $scope->getTraitReflection() !== null ? $scope->getTraitReflection()->getName() : null,
            $scope->getFunctionName(),
            $phpDoc->getText(),
        );
        /** @var VarTag[] $varTags */
        $varTags = $phpDocBlock->getVarTags();

        $variable = $node->var;

        if (!$variable instanceof Variable) {
            return [];
        }

        $variableName = $variable->name;

        if (!is_string($variableName)) {
            return [];
        }

        if (!isset($varTags[$variableName])) {
            return [];
        }

        $variableType = $varTags[$variableName]->getType();
        $valueType = $scope->getType($node->expr);

        if ($checkShapeOnly) {
            $valueType = $this->weakenTypeToKeepShapeOnly($valueType);
        }

        if ($variableType->accepts($valueType, $scope->isDeclareStrictTypes())->yes()) {
            return [];
        }

        $valueTypeString = $valueType->describe(VerbosityLevel::precise());
        $varPhpDocTypeString = $variableType->describe(VerbosityLevel::precise());

        if ($valueType->accepts($variableType, $scope->isDeclareStrictTypes())->yes() && $variableType->isArray()->no()) {
            if ($allowNarrowing) {
                return [];
            }

            return [
                "Invalid var phpdoc of \${$variableName}. Cannot narrow {$valueTypeString} to {$varPhpDocTypeString}",
            ];
        }

        return [
            "Invalid var phpdoc of \${$variableName}. Cannot assign {$valueTypeString} to {$varPhpDocTypeString}",
        ];
    }

    private function weakenTypeToKeepShapeOnly(Type $type): Type
    {
        return TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
            if ($type instanceof ArrayType || $type instanceof IterableType) {
                return $traverse($type); // keep array shapes, but forget all inner types
            }

            return new MixedType();
        });
    }

}
