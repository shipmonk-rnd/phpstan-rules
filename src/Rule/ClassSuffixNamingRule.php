<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function strlen;
use function substr_compare;

/**
 * @implements Rule<InClassNode>
 */
class ClassSuffixNamingRule implements Rule
{

    /**
     * @var array<class-string, string>
     */
    private array $superclassToSuffixMapping;

    /**
     * @param array<class-string, string> $superclassToSuffixMapping
     */
    public function __construct(array $superclassToSuffixMapping = [])
    {
        $this->superclassToSuffixMapping = $superclassToSuffixMapping;
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope
    ): array
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return [];
        }

        if ($classReflection->isAnonymous()) {
            return [];
        }

        foreach ($this->superclassToSuffixMapping as $superClass => $suffix) {
            if (!$classReflection->isSubclassOf($superClass)) {
                continue;
            }

            $className = $classReflection->getName();

            if (substr_compare($className, $suffix, -strlen($suffix)) !== 0) {
                $error = RuleErrorBuilder::message("Class name $className should end with $suffix suffix")
                    ->identifier('shipmonk.invalidClassSuffix')
                    ->build();
                return [$error];
            }
        }

        return [];
    }

}
