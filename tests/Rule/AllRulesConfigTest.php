<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<Rule>
 */
class AllRulesConfigTest extends RuleTestCase
{

    /**
     * @return Rule<Node>
     */
    protected function getRule(): Rule
    {
        return new class implements Rule {

            public function getNodeType(): string
            {
                return Node::class;
            }

            /**
             * @return string[]
             */
            public function processNode(Node $node, Scope $scope): array
            {
                return [];
            }

        };
    }

    /**
     * @return string[]
     */
    public static function getAdditionalConfigFiles(): array
    {
        return array_merge(
            parent::getAdditionalConfigFiles(),
            [__DIR__ . '/../../rules.neon'],
        );
    }

    public function test(): void
    {
        $this->analyseFile(__DIR__ . '/data/AllRulesConfig/code.php');
    }

}
