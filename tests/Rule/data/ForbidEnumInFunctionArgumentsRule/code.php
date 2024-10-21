<?php declare(strict_types = 1);

namespace ForbidEnumInFunctionArgumentsRule;

interface MarkingInterface {

}

enum SomeEnum: string implements MarkingInterface {
    case Bar = 'bar';
    case Baz = 'baz';
}

class SomeClass {}

class Test
{

    public function testAllFunctions()
    {
        $enums1 = [SomeEnum::Bar, SomeEnum::Baz];
        $enums2 = [SomeEnum::Bar];
        $enums3 = [SomeEnum::Baz];

        array_intersect($enums1, $enums2, $enums3); // reported by native PHPStan
        array_intersect_assoc($enums1, $enums2, $enums3); // reported by native PHPStan
        array_diff($enums1, $enums2, $enums3); // reported by native PHPStan
        array_diff_assoc($enums1, $enums2, $enums3); // reported by native PHPStan
        array_unique($enums1); // reported by native PHPStan
        array_combine($enums2, $enums3); // reported by native PHPStan
        sort($enums1); // error: Argument 1 in sort() cannot contain enum as the function causes unexpected results
        asort($enums1); // error: Argument 1 in asort() cannot contain enum as the function causes unexpected results
        arsort($enums1); // error: Argument 1 in arsort() cannot contain enum as the function causes unexpected results
        natsort($enums1); // reported by native PHPStan
        natcasesort($enums1); // reported by native PHPStan
        array_count_values($enums1); // reported by native PHPStan
        array_fill_keys($enums1, 1); // reported by native PHPStan
        array_flip($enums1); // reported by native PHPStan
        array_product($enums1); // error: Argument 1 in array_product() cannot contain enum as the function causes unexpected results
        array_sum($enums1); // error: Argument 1 in array_sum() cannot contain enum as the function causes unexpected results
        implode('', $enums1); // reported by native PHPStan
        in_array(SomeEnum::Bar, $enums1);
    }

    /**
     * @param SomeEnum&MarkingInterface $enumWithInterface
     * @param SomeEnum|SomeClass $enumOrNotEnum
     * @param list<SomeEnum>|array<SomeClass> $arrayOfEnumsOrNot
     */
    public function testUnionAndIntersection($enumWithInterface, $enumOrNotEnum, $arrayOfEnumsOrNot)
    {
        sort([$enumWithInterface]); // error: Argument 1 in sort() cannot contain enum as the function causes unexpected results
        sort([$enumOrNotEnum]); // error: Argument 1 in sort() cannot contain enum as the function causes unexpected results
        sort([$arrayOfEnumsOrNot]); // error: Argument 1 in sort() cannot contain enum as the function causes unexpected results
    }

    public function testArgumentsNormalization()
    {
        sort(flags: 0, array: [SomeEnum::Bar]); // error: Argument 1 in sort() cannot contain enum as the function causes unexpected results
    }

    public function testArrayUniqueWithSortRegular() {
        $enums = [SomeEnum::Bar, SomeEnum::Baz, SomeEnum::Bar];
        array_unique($enums, SORT_REGULAR); // https://3v4l.org/XF7Ua
    }

}
