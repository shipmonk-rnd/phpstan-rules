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
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\VerbosityLevel;
use function is_string;
use function str_contains;

/**
 * @implements Rule<Assign>
 */
class ForbidInvalidInlineVarPhpDocRule implements Rule
{

    private FileTypeMapper $fileTypeMapper;

    public function __construct(
        FileTypeMapper $fileTypeMapper,
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
        $phpDoc = $node->getDocComment();

        if ($phpDoc === null) {
            return [];
        }

        if (str_contains($phpDoc->getText(), 'check-shape-only')) {
            $checkShapeOnly = true; // this is needed for example when phpstan-doctrine deduces nullable field, but you added WHERE IS NOT NULL
        }

        $phpDocBlock = $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $scope->getClassReflection()?->getName(),
            $scope->getTraitReflection()?->getName(),
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

        return [
            "Invalid var phpdoc of \${$variableName}. Cannot assign {$valueTypeString} to {$varPhpDocTypeString}",
        ];
    }

    private function weakenTypeToKeepShapeOnly(Type $type): Type
    {
        return TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
            if ($type instanceof ArrayType) {
                return $traverse($type); // keep array shapes, but forget all inner types
            }

            return new MixedType();
        });
    }

}
