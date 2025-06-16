<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ClassPropertyNode>
 */
class EnforceReadonlyPublicPropertyRule implements Rule
{

    private PhpVersion $phpVersion;

    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }

    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    /**
     * @param ClassPropertyNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        if (!$this->phpVersion->supportsReadOnlyProperties()) {
            return [];
        }

        if (!$node->isPublic() || $node->isReadOnly() || $node->hasHooks()) {
            return [];
        }

        $classReflection = $node->getClassReflection();

        if (($classReflection->getNativeReflection()->getModifiers() & 65_536) !== 0) { // readonly class, since PHP 8.2
            return [];
        }

        $error = RuleErrorBuilder::message("Public property `{$node->getName()}` not marked as readonly.")
            ->identifier('shipmonk.publicPropertyNotReadonly')
            ->build();
        return [$error];
    }

}
