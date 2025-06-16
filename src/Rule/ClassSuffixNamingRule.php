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
use function strlen;
use function substr_compare;

/**
 * @implements Rule<InClassNode>
 */
class ClassSuffixNamingRule implements Rule
{

    private ReflectionProvider $reflectionProvider;

    /**
     * @var array<class-string, string>
     */
    private array $superclassToSuffixMapping;

    /**
     * @param array<class-string, string> $superclassToSuffixMapping
     */
    public function __construct(
        ReflectionProvider $reflectionProvider,
        array $superclassToSuffixMapping = []
    )
    {
        foreach ($superclassToSuffixMapping as $className => $suffix) {
            if (!$reflectionProvider->hasClass($className)) {
                throw new LogicException("Class $className used in 'superclassToSuffixMapping' does not exist");
            }
        }

        $this->reflectionProvider = $reflectionProvider;
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
            $superClassReflection = $this->reflectionProvider->getClass($superClass);

            if (!$classReflection->isSubclassOfClass($superClassReflection)) {
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
