<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\Node as PhpParserNode;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\UnionType;
use PHPStan\Analyser\NameScope;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\Printer;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Ast\Node as PhpDocRootNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function array_values;
use function count;
use function get_object_vars;
use function implode;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function spl_object_id;

/**
 * @implements Rule<PhpParserNode>
 */
class ForbidNotNormalizedTypeRule implements Rule
{

    private FileTypeMapper $fileTypeMapper;

    private TypeNodeResolver $typeNodeResolver;

    private Printer $phpParserPrinter;

    private bool $checkDisjunctiveNormalForm;

    /**
     * @var array<int, true>
     */
    private array $processedDocComments = [];

    public function __construct(
        FileTypeMapper $fileTypeMapper,
        TypeNodeResolver $typeNodeResolver,
        Printer $phpParserPrinter,
        bool $checkDisjunctiveNormalForm
    )
    {
        $this->fileTypeMapper = $fileTypeMapper;
        $this->typeNodeResolver = $typeNodeResolver;
        $this->phpParserPrinter = $phpParserPrinter;
        $this->checkDisjunctiveNormalForm = $checkDisjunctiveNormalForm;
    }

    public function getNodeType(): string
    {
        return PhpParserNode::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        PhpParserNode $node,
        Scope $scope
    ): array
    {
        if ($node instanceof FunctionLike) {
            return array_merge(
                $this->checkParamAndReturnAndThrowsPhpDoc($node, $scope),
                $this->checkParamAndReturnNativeType($node, $scope),
            );
        }

        if ($node instanceof Property) {
            return array_merge(
                $this->checkPropertyPhpDoc($node, $scope),
                $this->checkPropertyNativeType($node, $scope),
            );
        }

        if ($node instanceof Catch_) {
            return $this->checkCatchNativeType($node, $scope);
        }

        return $this->checkInlineVarDoc($node, $scope);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkCatchNativeType(
        Catch_ $node,
        Scope $scope
    ): array
    {
        $multiTypeNode = new UnionType($node->types, $node->getAttributes());
        return $this->processMultiTypePhpParserNode($multiTypeNode, $scope, 'catch statement');
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkParamAndReturnAndThrowsPhpDoc(
        FunctionLike $node,
        Scope $scope
    ): array
    {
        $errors = [];

        $resolvedPhpDoc = $this->resolvePhpDoc($node, $scope);

        if ($resolvedPhpDoc === null) {
            return [];
        }

        $nameScope = $resolvedPhpDoc->getNullableNameScope();

        if ($nameScope === null) {
            return [];
        }

        foreach ($resolvedPhpDoc->getPhpDocNodes() as $phpdocNode) {
            $errors = array_merge(
                $errors,
                $this->processParamTags($node, $phpdocNode->getParamTagValues(), $nameScope),
                $this->processReturnTags($node, $phpdocNode->getReturnTagValues(), $nameScope),
                $this->processThrowsTags($node, $phpdocNode->getThrowsTagValues(), $nameScope),
            );
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkPropertyNativeType(
        Property $node,
        Scope $scope
    ): array
    {
        $errors = [];

        if ($node->type !== null) {
            $propertyName = $this->getPropertyNameFromNativeNode($node);

            foreach ($this->extractUnionIntersectionPhpParserNodes($node->type) as $multiTypeNode) {
                $newErrors = $this->processMultiTypePhpParserNode($multiTypeNode, $scope, "property \${$propertyName}");
                $errors = array_merge($errors, $newErrors);
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkParamAndReturnNativeType(
        FunctionLike $node,
        Scope $scope
    ): array
    {
        $errors = [];

        if ($node->getReturnType() !== null) {
            foreach ($this->extractUnionIntersectionPhpParserNodes($node->getReturnType()) as $multiTypeNode) {
                $newErrors = $this->processMultiTypePhpParserNode($multiTypeNode, $scope, 'return');
                $errors = array_merge($errors, $newErrors);
            }
        }

        foreach ($node->getParams() as $param) {
            $paramType = $param->type;

            if ($paramType === null) {
                continue;
            }

            $parameterName = $this->getParameterNameFromNativeNode($param);

            foreach ($this->extractUnionIntersectionPhpParserNodes($paramType) as $multiTypeNode) {
                $newErrors = $this->processMultiTypePhpParserNode($multiTypeNode, $scope, "parameter \${$parameterName}");
                $errors = array_merge($errors, $newErrors);
            }
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkPropertyPhpDoc(
        Property $node,
        Scope $scope
    ): array
    {
        $errors = [];

        $resolvedPhpDoc = $this->resolvePhpDoc($node, $scope);

        if ($resolvedPhpDoc === null) {
            return [];
        }

        $nameScope = $resolvedPhpDoc->getNullableNameScope();

        if ($nameScope === null) {
            return [];
        }

        foreach ($resolvedPhpDoc->getPhpDocNodes() as $phpdocNode) {
            $errors = array_merge($errors, $this->processVarTags($node, $phpdocNode->getVarTagValues(), $nameScope));
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function checkInlineVarDoc(
        PhpParserNode $node,
        Scope $scope
    ): array
    {
        $docComment = $node->getDocComment();

        if ($docComment === null) {
            return [];
        }

        $docCommendSplId = spl_object_id($docComment);

        if (isset($this->processedDocComments[$docCommendSplId])) {
            return []; // the instance is shared in all nodes where this vardoc is used (e.g. Expression, Assign, Variable for $a = $b)
        }

        $resolvedPhpDoc = $this->resolvePhpDoc($node, $scope);

        if ($resolvedPhpDoc === null) {
            return [];
        }

        $nameScope = $resolvedPhpDoc->getNullableNameScope();

        if ($nameScope === null) {
            return [];
        }

        $errors = [];

        foreach ($resolvedPhpDoc->getPhpDocNodes() as $phpdocNode) {
            $errors = array_merge($errors, $this->processVarTags($node, $phpdocNode->getVarTagValues(), $nameScope));
        }

        $this->processedDocComments[$docCommendSplId] = true;

        return $errors;
    }

    private function resolvePhpDoc(
        PhpParserNode $node,
        Scope $scope
    ): ?ResolvedPhpDocBlock
    {
        $docComment = $node->getDocComment();

        if ($docComment === null) {
            return null;
        }

        return $this->fileTypeMapper->getResolvedPhpDoc(
            $scope->getFile(),
            $scope->getClassReflection() === null ? null : $scope->getClassReflection()->getName(),
            $scope->getTraitReflection() === null ? null : $scope->getTraitReflection()->getName(),
            $this->getFunctionName($node),
            $docComment->getText(),
        );
    }

    private function getFunctionName(PhpParserNode $node): ?string
    {
        if ($node instanceof ClassMethod || $node instanceof Function_) {
            return $node->name->name;
        }

        return null;
    }

    /**
     * @param array<ParamTagValueNode> $paramTagValues
     * @return list<IdentifierRuleError>
     */
    public function processParamTags(
        PhpParserNode $sourceNode,
        array $paramTagValues,
        NameScope $nameSpace
    ): array
    {
        $errors = [];

        foreach ($paramTagValues as $paramTagValue) {
            $line = $this->getPhpDocLine($sourceNode, $paramTagValue);

            foreach ($this->extractUnionAndIntersectionPhpDocTypeNodes($paramTagValue->type, $line) as $multiTypeNode) {
                $newErrors = $this->processMultiTypePhpDocNode(
                    $multiTypeNode,
                    $nameSpace,
                    "parameter {$paramTagValue->parameterName}",
                );
                $errors = array_merge($errors, $newErrors);
            }
        }

        return $errors;
    }

    /**
     * @param array<VarTagValueNode> $varTagValues
     * @return list<IdentifierRuleError>
     */
    public function processVarTags(
        PhpParserNode $originalNode,
        array $varTagValues,
        NameScope $nameSpace
    ): array
    {
        $errors = [];

        foreach ($varTagValues as $varTagValue) {
            $line = $this->getPhpDocLine($originalNode, $varTagValue);

            foreach ($this->extractUnionAndIntersectionPhpDocTypeNodes($varTagValue->type, $line) as $multiTypeNode) {
                $identification = $varTagValue->variableName !== ''
                    ? "variable {$varTagValue->variableName}"
                    : null;

                $newErrors = $this->processMultiTypePhpDocNode(
                    $multiTypeNode,
                    $nameSpace,
                    $identification,
                );
                $errors = array_merge($errors, $newErrors);
            }
        }

        return $errors;
    }

    /**
     * @param array<ReturnTagValueNode> $returnTagValues
     * @return list<IdentifierRuleError>
     */
    public function processReturnTags(
        PhpParserNode $originalNode,
        array $returnTagValues,
        NameScope $nameSpace
    ): array
    {
        $errors = [];

        foreach ($returnTagValues as $returnTagValue) {
            $line = $this->getPhpDocLine($originalNode, $returnTagValue);

            foreach ($this->extractUnionAndIntersectionPhpDocTypeNodes($returnTagValue->type, $line) as $multiTypeNode) {
                $newErrors = $this->processMultiTypePhpDocNode($multiTypeNode, $nameSpace, 'return');
                $errors = array_merge($errors, $newErrors);
            }
        }

        return $errors;
    }

    /**
     * @param array<ThrowsTagValueNode> $throwsTagValues
     * @return list<IdentifierRuleError>
     */
    public function processThrowsTags(
        PhpParserNode $originalNode,
        array $throwsTagValues,
        NameScope $nameSpace
    ): array
    {
        $thrownTypes = [];

        foreach ($throwsTagValues as $throwsTagValue) {
            $line = $this->getPhpDocLine($originalNode, $throwsTagValue);
            $multiTypeNodes = $this->extractUnionAndIntersectionPhpDocTypeNodes($throwsTagValue->type, $line);

            if ($multiTypeNodes === []) {
                $innerType = $throwsTagValue->type;
                $innerType->setAttribute('line', $line);

                $thrownTypes[] = $innerType;
            } else {
                foreach ($multiTypeNodes as $multiTypeNode) {
                    foreach ($multiTypeNode->types as $typeNode) {
                        $thrownTypes[] = $typeNode;
                    }
                }
            }
        }

        $unionNode = new UnionTypeNode($thrownTypes);
        return $this->processMultiTypePhpDocNode($unionNode, $nameSpace, 'throws');
    }

    /**
     * @return list<UnionTypeNode|IntersectionTypeNode>
     */
    private function extractUnionAndIntersectionPhpDocTypeNodes(
        TypeNode $typeNode,
        int $line
    ): array
    {
        /** @var list<UnionTypeNode|IntersectionTypeNode> $nodes */
        $nodes = [];
        $this->traversePhpDocTypeNode($typeNode, static function (TypeNode $typeNode) use (&$nodes): void {
            if ($typeNode instanceof UnionTypeNode || $typeNode instanceof IntersectionTypeNode) {
                $nodes[] = $typeNode;
            }

            if ($typeNode instanceof NullableTypeNode) {
                $nodes[] = new UnionTypeNode([$typeNode->type, new IdentifierTypeNode('null')]);
            }
        });

        foreach ($nodes as $node) {
            foreach ($node->types as $innerType) {
                $innerType->setAttribute('line', $line);
            }
        }

        return $nodes;
    }

    /**
     * @return list<IntersectionType|UnionType>
     */
    private function extractUnionIntersectionPhpParserNodes(PhpParserNode $node): array
    {
        $multiTypeNodes = [];

        if ($node instanceof NullableType) {
            $multiTypeNodes[] = new UnionType([$node->type, new Identifier('null')], $node->getAttributes());
        }

        if ($node instanceof UnionType) {
            $multiTypeNodes[] = $node;

            foreach ($node->types as $innerType) {
                if ($innerType instanceof IntersectionType) {
                    $multiTypeNodes[] = $innerType;
                }
            }
        }

        if ($node instanceof IntersectionType) {
            $multiTypeNodes[] = $node;
        }

        return $multiTypeNodes;
    }

    /**
     * @param mixed $type
     * @param callable(TypeNode): void $callback
     */
    private function traversePhpDocTypeNode(
        $type,
        callable $callback
    ): void
    {
        if (is_array($type)) {
            foreach ($type as $item) {
                $this->traversePhpDocTypeNode($item, $callback);
            }
        }

        if ($type instanceof TypeNode) {
            $callback($type);
        }

        if (is_object($type)) {
            $this->traversePhpDocTypeNode(get_object_vars($type), $callback);
        }
    }

    /**
     * @param IntersectionType|UnionType $multiTypeNode
     * @return list<IdentifierRuleError>
     */
    private function processMultiTypePhpParserNode(
        ComplexType $multiTypeNode,
        Scope $scope,
        string $identification
    ): array
    {
        $innerTypeNodes = array_values($multiTypeNode->types);
        $multiTypeNodeString = $this->printPhpParserNode($multiTypeNode);

        $errors = [];
        $countOfNodeTypes = count($innerTypeNodes);

        foreach ($innerTypeNodes as $i => $iValue) {
            for ($j = $i + 1; $j < $countOfNodeTypes; $j++) {
                $typeNodeA = $iValue;
                $typeNodeB = $innerTypeNodes[$j]; // @phpstan-ignore offsetAccess.notFound

                $typeA = $scope->getFunctionType($typeNodeA, false, false);
                $typeB = $scope->getFunctionType($typeNodeB, false, false);

                $typeNodeAString = $this->printPhpParserNode($typeNodeA);
                $typeNodeBString = $this->printPhpParserNode($typeNodeB);

                if ($typeA->isSuperTypeOf($typeB)->yes()) {
                    $errors[] = RuleErrorBuilder::message("Found non-normalized type {$multiTypeNodeString} for {$identification}: {$typeNodeBString} is a subtype of {$typeNodeAString}.")
                        ->line($multiTypeNode->getStartLine())
                        ->identifier('shipmonk.nonNormalizedType')
                        ->build();
                    continue;
                }

                if ($typeB->isSuperTypeOf($typeA)->yes()) {
                    $errors[] = RuleErrorBuilder::message("Found non-normalized type {$multiTypeNodeString} for {$identification}: {$typeNodeAString} is a subtype of {$typeNodeBString}.")
                        ->line($multiTypeNode->getStartLine())
                        ->identifier('shipmonk.nonNormalizedType')
                        ->build();
                }
            }
        }

        return $errors;
    }

    private function printPhpParserNode(PhpParserNode $node): string
    {
        $nodeCopy = clone $node;
        $nodeCopy->setAttribute('comments', []); // avoid printing with surrounding line comments
        return $this->phpParserPrinter->prettyPrint([$nodeCopy]);
    }

    /**
     * @param UnionTypeNode|IntersectionTypeNode $multiTypeNode
     * @return list<IdentifierRuleError>
     */
    private function processMultiTypePhpDocNode(
        TypeNode $multiTypeNode,
        NameScope $nameSpace,
        ?string $identification
    ): array
    {
        $errors = [];
        $innerTypeNodes = array_values($multiTypeNode->types); // ensure list
        $forWhat = $identification !== null ? " for $identification" : '';

        if ($this->checkDisjunctiveNormalForm && $multiTypeNode instanceof IntersectionTypeNode) {
            foreach ($multiTypeNode->types as $type) {
                if ($type instanceof UnionTypeNode) {
                    $dnf = $this->typeNodeResolver->resolve($multiTypeNode, $nameSpace)->describe(VerbosityLevel::typeOnly());
                    $line = $this->extractLineFromPhpDocTypeNode($type);

                    $errors[] = RuleErrorBuilder::message("Found non-normalized type {$multiTypeNode}{$forWhat}: this is not disjunctive normal form, use {$dnf}")
                        ->line($line)
                        ->identifier('shipmonk.nonNormalizedType')
                        ->build();
                }
            }
        }

        $countOfNodeTypes = count($innerTypeNodes);

        foreach ($innerTypeNodes as $i => $iValue) {
            for ($j = $i + 1; $j < $countOfNodeTypes; $j++) {
                $typeNodeA = $iValue;
                $typeNodeB = $innerTypeNodes[$j]; // @phpstan-ignore offsetAccess.notFound

                $typeA = $this->typeNodeResolver->resolve($typeNodeA, $nameSpace);
                $typeB = $this->typeNodeResolver->resolve($typeNodeB, $nameSpace);

                $typeALine = $this->extractLineFromPhpDocTypeNode($typeNodeA);
                $typeBLine = $this->extractLineFromPhpDocTypeNode($typeNodeB);

                if ($typeA->isSuperTypeOf($typeB)->yes()) {
                    $errors[] = RuleErrorBuilder::message("Found non-normalized type {$multiTypeNode}{$forWhat}: {$typeNodeB} is a subtype of {$typeNodeA}.")
                        ->line($typeBLine)
                        ->identifier('shipmonk.nonNormalizedType')
                        ->build();
                    continue;
                }

                if ($typeB->isSuperTypeOf($typeA)->yes()) {
                    $errors[] = RuleErrorBuilder::message("Found non-normalized type {$multiTypeNode}{$forWhat}: {$typeNodeA} is a subtype of {$typeNodeB}.")
                        ->line($typeALine)
                        ->identifier('shipmonk.nonNormalizedType')
                        ->build();
                }
            }
        }

        return $errors;
    }

    private function extractLineFromPhpDocTypeNode(TypeNode $node): int
    {
        $line = $node->getAttribute('line');

        if (!is_int($line)) {
            throw new LogicException('Missing custom line attribute in node: ' . $node);
        }

        return $line;
    }

    private function getPropertyNameFromNativeNode(Property $node): string
    {
        $propertyNames = [];

        foreach ($node->props as $propertyProperty) {
            $propertyNames[] = $propertyProperty->name->name;
        }

        return implode(',', $propertyNames);
    }

    private function getPhpDocLine(
        PhpParserNode $node,
        PhpDocRootNode $phpDocNode
    ): int
    {
        /** @var int|null $phpDocTagLine */
        $phpDocTagLine = $phpDocNode->getAttribute('startLine');
        $phpDoc = $node->getDocComment();

        if ($phpDocTagLine === null || $phpDoc === null) {
            return $node->getStartLine();
        }

        return $phpDoc->getStartLine() + $phpDocTagLine - 1;
    }

    private function getParameterNameFromNativeNode(Param $param): string
    {
        if ($param->var instanceof Variable && is_string($param->var->name)) {
            return $param->var->name;
        }

        throw new LogicException('Unexpected parameter: ' . $this->printPhpParserNode($param));
    }

}
