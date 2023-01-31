<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<ClassPropertyNode>
 */
class EnforceReadonlyPublicPropertyRule implements Rule
{

    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    /**
     * @param ClassPropertyNode $node
     * @return list<string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->isPublic() || $node->isReadOnly()) {
            return [];
        }

        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return [];
        }

        if (($classReflection->getNativeReflection()->getModifiers() & 65_536) !== 0) { // readonly class, since PHP 8.2
            return [];
        }

        return ["Public property `{$node->getName()}` not marked as readonly."];
    }

}
