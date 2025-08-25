<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Rule\Data\ForbidUnusedClosureParametersRule;

class ClosureUnusedArgumentTestClass
{

    public function testUnusedParam(): void
    {
        $narrowAisleOccurrenceIds = [1, 2, 3];

        // Arrow functions - This should trigger an error - $occurrenceIds is not used (trailing unused param)
        $callback = fn (array $occurrenceIds) => $narrowAisleOccurrenceIds; // error: Arrow function parameter $occurrenceIds is unused

        // This should not trigger an error - both parameters are used
        $usedCallback = fn (array $ids, string $type) => str_contains($type, 'narrow') ? $ids : [];

        // This should not trigger an error - single parameter is used
        $singleUsedCallback = fn (array $data) => count($data);

        // This should trigger an error - only $unused is trailing unused (but $used is used)
        $mixedCallback = fn (array $used, string $unused) => count($used); // error: Arrow function parameter $unused is unused

        // This should NOT trigger an error - $key is unused but needed for $receivingItem position
        $positionCallback = fn (int $key, \stdClass $receivingItem) => $receivingItem->id === 123;

        // This should trigger an error - multiple trailing unused parameters
        $multipleUnusedCallback = fn (array $used, string $first, int $second) => count($used); // error: Arrow function parameter $first is unused // error: Arrow function parameter $second is unused

        // This should trigger an error - all parameters are unused (all trailing)
        $allUnusedCallback = fn (array $first, string $second, int $third) => 'constant'; // error: Arrow function parameter $first is unused // error: Arrow function parameter $second is unused // error: Arrow function parameter $third is unused

        // This should not trigger an error - no parameters
        $noParamsCallback = fn () => 'constant';

        // This should not trigger an error - unused in middle, but used at end
        $unusedInMiddleCallback = fn (int $unused1, string $unused2, array $used) => count($used);

        // Regular closures - This should trigger an error - $occurrenceIds is not used (trailing unused param)
        $closureCallback = function (array $occurrenceIds) { // error: Closure parameter $occurrenceIds is unused
            return $narrowAisleOccurrenceIds;
        };

        // This should not trigger an error - both parameters are used
        $usedClosure = function (array $ids, string $type) {
            return str_contains($type, 'narrow') ? $ids : [];
        };

        // This should trigger an error - only $unused is trailing unused
        $mixedClosure = function (array $used, string $unused) { // error: Closure parameter $unused is unused
            return count($used);
        };

        // This should NOT trigger an error - $key is unused but needed for $receivingItem position
        $positionClosure = function (int $key, \stdClass $receivingItem) {
            return $receivingItem->id === 123;
        };

        // This should trigger an error - multiple trailing unused parameters
        $multipleUnusedClosure = function (array $used, string $first, int $second) { // error: Closure parameter $first is unused // error: Closure parameter $second is unused
            return count($used);
        };

        // This should not trigger an error - unused in middle, but used at end
        $unusedInMiddleClosure = function (int $unused1, string $unused2, array $used) {
            return count($used);
        };
    }

}
