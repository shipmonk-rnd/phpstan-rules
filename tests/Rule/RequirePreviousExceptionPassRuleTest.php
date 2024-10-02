<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule;

use LogicException;
use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use ShipMonk\PHPStan\RuleTestCase;

/**
 * @extends RuleTestCase<RequirePreviousExceptionPassRule>
 */
class RequirePreviousExceptionPassRuleTest extends RuleTestCase
{

    private ?bool $reportEvenIfExceptionIsNotAcceptableByRethrownOne = null;

    protected function getRule(): Rule
    {
        if ($this->reportEvenIfExceptionIsNotAcceptableByRethrownOne === null) {
            throw new LogicException('Testcase need to initialize this');
        }

        return new RequirePreviousExceptionPassRule(
            self::getContainer()->getByType(Printer::class),
            $this->reportEvenIfExceptionIsNotAcceptableByRethrownOne,
        );
    }

    public function testWithAcceptCheck(): void
    {
        $this->reportEvenIfExceptionIsNotAcceptableByRethrownOne = false;
        $this->analyseFile(__DIR__ . '/data/RequirePreviousExceptionPassRule/with-accept-check.php');
    }

    public function testWithoutAcceptCheck(): void
    {
        $this->reportEvenIfExceptionIsNotAcceptableByRethrownOne = true;
        $this->analyseFile(__DIR__ . '/data/RequirePreviousExceptionPassRule/without-accept-check.php');
    }

}
