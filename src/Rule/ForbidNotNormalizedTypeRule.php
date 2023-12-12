<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\Node as PhpParserNode;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\UnionType;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter\Standard as PhpParserPrinter;
use PHPStan\Analyser\NameScope;
use PHPStan\Analyser\Scope;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Ast\Node as PhpDocRootNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\VerbosityLevel;
use ShipMonk\PHPStan\Visitor\UnionIntersectionExtractorVisitor;
use function array_merge;
use function array_values;
use function count;
use function get_object_vars;
use function implode;
use function is_array;
use function is_object;
use function is_string;
use function spl_object_hash;

/**
 * @implements Rule<PhpParserNode>
 */
class ForbidNotNormalizedTypeRule implements Rule
{

    private FileTypeMapper $fileTypeMapper;

    private TypeNodeResolver $typeNodeResolver;

    private PhpParserPrinter $phpParserPrinter;

    /**
     * @var array<string, true>
     */
    private array $processedDocComments = [];

    public function __construct(
        FileTypeMapper $fileTypeMapper,
        TypeNodeResolver $typeNodeResolver,
        PhpParserPrinter $phpParserPrinter
    )
    {
        $this->fileTypeMapper = $fileTypeMapper;
        $this->typeNodeResolver = $typeNodeResolver;
        $this->phpParserPrinter = $phpParserPrinter;
    }

    public function getNodeType(): string
    {
        return PhpParserNode::class;
    }

    /**
     * @return list<RuleError>
     */
    public function processNode(
        PhpParserNode $node,
        Scope $scope
    ): array
    {
        if ($node instanceof FunctionLike) {
            return array_merge(
                $this->checkParamAndReturnPhpDoc($node, $scope),
                $this->checkParamAndReturnNativeType($node, $scope),
            );
        }

        if ($node instanceof Property) {
            return array_merge(
                $this->checkPropertyPhpDoc($node, $scope),
                $this->checkPropertyNativeType($node, $scope),
            );
        }

        return $this->checkInlineVarDoc($node, $scope);
    }

    /**
     * @return list<RuleError>
     */
    private function checkParamAndReturnPhpDoc(
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
            );
        }

        return $errors;
    }

    /**
     * @return list<RuleError>
     */
    private function checkPropertyNativeType(Property $node, Scope $scope): array
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
     * @return list<RuleError>
     */
    private function checkParamAndReturnNativeType(FunctionLike $node, Scope $scope): array
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
     * @return list<RuleError>
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
     * @return list<RuleError>
     */
    private function checkInlineVarDoc(PhpParserNode $node, Scope $scope): array
    {
        $docComment = $node->getDocComment();

        if ($docComment === null) {
            return [];
        }

        $docCommendHash = spl_object_hash($docComment);

        if (isset($this->processedDocComments[$docCommendHash])) {
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

        $this->processedDocComments[$docCommendHash] = true;

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
     * @return list<RuleError>
     */
    public function processParamTags(
        PhpParserNode $sourceNode,
        array $paramTagValues,
        NameScope $nameSpace
    ): array
    {
        $errors = [];

        foreach ($paramTagValues as $paramTagValue) {
            foreach ($this->extractUnionAndIntersectionPhpDocTypeNodes($paramTagValue->type) as $multiTypeNode) {
                $newErrors = $this->processMultiTypePhpDocNode(
                    $multiTypeNode,
                    $nameSpace,
                    "parameter {$paramTagValue->parameterName}",
                    $this->getPhpDocLine($sourceNode, $paramTagValue),
                );
                $errors = array_merge($errors, $newErrors);
            }
        }

        return $errors;
    }

    /**
     * @param array<VarTagValueNode> $varTagValues
     * @return list<RuleError>
     */
    public function processVarTags(
        PhpParserNode $originalNode,
        array $varTagValues,
        NameScope $nameSpace
    ): array
    {
        $errors = [];

        foreach ($varTagValues as $varTagValue) {
            foreach ($this->extractUnionAndIntersectionPhpDocTypeNodes($varTagValue->type) as $multiTypeNode) {
                $identification = $varTagValue->variableName !== ''
                    ? "variable {$varTagValue->variableName}"
                    : null;

                $newErrors = $this->processMultiTypePhpDocNode(
                    $multiTypeNode,
                    $nameSpace,
                    $identification,
                    $this->getPhpDocLine($originalNode, $varTagValue),
                );
                $errors = array_merge($errors, $newErrors);
            }
        }

        return $errors;
    }

    /**
     * @param array<ReturnTagValueNode> $returnTagValues
     * @return list<RuleError>
     */
    public function processReturnTags(
        PhpParserNode $originalNode,
        array $returnTagValues,
        NameScope $nameSpace
    ): array
    {
        $errors = [];

        foreach ($returnTagValues as $returnTagValue) {
            foreach ($this->extractUnionAndIntersectionPhpDocTypeNodes($returnTagValue->type) as $multiTypeNode) {
                $newErrors = $this->processMultiTypePhpDocNode($multiTypeNode, $nameSpace, 'return', $this->getPhpDocLine($originalNode, $returnTagValue));
                $errors = array_merge($errors, $newErrors);
            }
        }

        return $errors;
    }

    /**
     * @return list<UnionTypeNode|IntersectionTypeNode>
     */
    private function extractUnionAndIntersectionPhpDocTypeNodes(TypeNode $typeNode): array
    {
        $nodes = [];
        $this->traversePhpDocTypeNode($typeNode, static function (TypeNode $typeNode) use (&$nodes): void {
            if ($typeNode instanceof UnionTypeNode || $typeNode instanceof IntersectionTypeNode) {
                $nodes[] = $typeNode;
            }

            if ($typeNode instanceof NullableTypeNode) {
                $nodes[] = new UnionTypeNode([$typeNode->type, new IdentifierTypeNode('null')]);
            }
        });
        return $nodes;
    }

    /**
     * @return list<IntersectionType|UnionType>
     */
    private function extractUnionIntersectionPhpParserNodes(PhpParserNode $node): array
    {
        $extractor = new UnionIntersectionExtractorVisitor();
        $phpParserNodeTraverser = new NodeTraverser();
        $phpParserNodeTraverser->addVisitor($extractor);
        $phpParserNodeTraverser->traverse([$node]);

        return $extractor->getNodes();
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
            foreach (get_object_vars($type) as $item) {
                $this->traversePhpDocTypeNode($item, $callback);
            }
        }
    }

    /**
     * @param IntersectionType|UnionType $multiTypeNode
     * @return list<RuleError>
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
                $typeNodeB = $innerTypeNodes[$j];

                $typeA = $scope->getFunctionType($typeNodeA, false, false);
                $typeB = $scope->getFunctionType($typeNodeB, false, false);

                $typeNodeAString = $this->printPhpParserNode($typeNodeA);
                $typeNodeBString = $this->printPhpParserNode($typeNodeB);

                if ($typeA->isSuperTypeOf($typeB)->yes()) {
                    $errors[] = RuleErrorBuilder::message("Found non-normalized type {$multiTypeNodeString} for {$identification}: {$typeNodeBString} is a subtype of {$typeNodeAString}.")
                        ->line($multiTypeNode->getLine())
                        ->identifier('shipmonk.nonNormalizedType')
                        ->build();
                    continue;
                }

                if ($typeB->isSuperTypeOf($typeA)->yes()) {
                    $errors[] = RuleErrorBuilder::message("Found non-normalized type {$multiTypeNodeString} for {$identification}: {$typeNodeAString} is a subtype of {$typeNodeBString}.")
                        ->line($multiTypeNode->getLine())
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
     * @return list<RuleError>
     */
    private function processMultiTypePhpDocNode(
        TypeNode $multiTypeNode,
        NameScope $nameSpace,
        ?string $identification,
        int $line
    ): array
    {
        $errors = [];
        $innerTypeNodes = array_values($multiTypeNode->types); // ensure list
        $forWhat = $identification !== null ? " for $identification" : '';

        if ($multiTypeNode instanceof IntersectionTypeNode) {
            foreach ($multiTypeNode->types as $type) {
                if ($type instanceof UnionTypeNode) {
                    $dnf = $this->typeNodeResolver->resolve($multiTypeNode, $nameSpace)->describe(VerbosityLevel::typeOnly());

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
                $typeNodeB = $innerTypeNodes[$j];

                $typeA = $this->typeNodeResolver->resolve($typeNodeA, $nameSpace);
                $typeB = $this->typeNodeResolver->resolve($typeNodeB, $nameSpace);

                if ($typeA->isSuperTypeOf($typeB)->yes()) {
                    $errors[] = RuleErrorBuilder::message("Found non-normalized type {$multiTypeNode}{$forWhat}: {$typeNodeB} is a subtype of {$typeNodeA}.")
                        ->line($line)
                        ->identifier('shipmonk.nonNormalizedType')
                        ->build();
                    continue;
                }

                if ($typeB->isSuperTypeOf($typeA)->yes()) {
                    $errors[] = RuleErrorBuilder::message("Found non-normalized type {$multiTypeNode}{$forWhat}: {$typeNodeA} is a subtype of {$typeNodeB}.")
                        ->line($line)
                        ->identifier('shipmonk.nonNormalizedType')
                        ->build();
                }
            }
        }

        return $errors;
    }

    private function getPropertyNameFromNativeNode(Property $node): string
    {
        $propertyNames = [];

        foreach ($node->props as $propertyProperty) {
            $propertyNames[] = $propertyProperty->name->name;
        }

        return implode(',', $propertyNames);
    }

    private function getPhpDocLine(PhpParserNode $node, PhpDocRootNode $phpDocNode): int
    {
        /** @var int|null $phpDocTagLine */
        $phpDocTagLine = $phpDocNode->getAttribute('startLine');
        $phpDoc = $node->getDocComment();

        if ($phpDocTagLine === null || $phpDoc === null) {
            return $node->getLine();
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
