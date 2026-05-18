<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function str_ends_with;

/**
 * @implements Rule<InClassNode>
 */
class ClassSuffixNamingRule implements Rule
{

    private bool $validated = false;

    /**
     * @param array<class-string, string> $superclassToSuffixMapping
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly array $superclassToSuffixMapping = [],
    )
    {
    }

    private function validateSuperclassToSuffixMapping(): void
    {
        if ($this->validated) {
            return;
        }

        foreach ($this->superclassToSuffixMapping as $className => $suffix) {
            if (!$this->reflectionProvider->hasClass($className)) {
                throw new LogicException("Class $className used in 'superclassToSuffixMapping' does not exist");
            }
        }
        $this->validated = true;
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
        Scope $scope,
    ): array
    {
        $this->validateSuperclassToSuffixMapping();
        if ($this->superclassToSuffixMapping === []) {
            return [];
        }

        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return [];
        }

        if ($classReflection->isAnonymous()) {
            return [];
        }

        foreach ($this->superclassToSuffixMapping as $superClass => $suffix) {
            $superClassReflection = $this->reflectionProvider->getClass($superClass);

            if (!$classReflection->isSubclassOfClass($superClassReflection)) {
                continue;
            }

            $className = $classReflection->getName();

            if (!str_ends_with($className, $suffix)) {
                $error = RuleErrorBuilder::message("Class name $className should end with $suffix suffix")
                    ->identifier('shipmonk.invalidClassSuffix')
                    ->build();
                return [$error];
            }
        }

        return [];
    }

}
